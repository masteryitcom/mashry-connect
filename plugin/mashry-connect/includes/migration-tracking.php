<?php
// migration-tracking.php
// Change detection and batch migration processing with hash-based incremental updates

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create tracking table for migration history
 * Stores data hash, export timestamps, and status for each item
 */
function mashry_connect_create_tracking_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        migration_type varchar(50) NOT NULL,
        item_id bigint(20) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        batch_number int(11) DEFAULT 0,
        error_message text,
        migrated_data longtext,
        data_hash varchar(64),
        last_exported_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_migration_item (migration_type, item_id),
        KEY migration_type (migration_type),
        KEY item_id (item_id),
        KEY status (status),
        KEY data_hash (data_hash),
        KEY last_exported_at (last_exported_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Compare current data with previously exported data
 * Uses SHA-256 hash with ksort() to ensure consistent ordering
 * Returns array with both 'changed' (bool) and 'hash' (string) to avoid recalculation
 * 
 * First export always returns changed=true to ensure data is stored
 * Subsequent exports compare hashes: if identical, data hasn't changed
 * 
 * @param string $type Migration type (users, products, categories)
 * @param int $item_id ID of the item being checked
 * @param array $current_data Current item data to compare
 * @return array ['changed' => bool, 'hash' => string]
 */
function mashry_connect_has_data_changed($type, $item_id, $current_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    // Calculate hash once - use ksort() to ensure consistent key ordering
    // This prevents json_encode() from producing different hashes for identical data
    $sorted_data = $current_data;
    ksort($sorted_data);
    $current_hash = hash('sha256', json_encode($sorted_data));
    
    // Query database for previous export record
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT data_hash, last_exported_at FROM $table_name 
             WHERE migration_type = %s AND item_id = %d LIMIT 1",
            $type,
            $item_id
        )
    );
    
    // First time: no record exists yet
    if ($row === null) {
        return ['changed' => true, 'hash' => $current_hash];
    }
    
    // First export: record exists but last_exported_at is NULL
    if (is_null($row->last_exported_at)) {
        return ['changed' => true, 'hash' => $current_hash];
    }
    
    // Compare hashes - if identical, data hasn't changed
    // Return both the result AND the hash to avoid recalculation in calling code
    $has_changed = $current_hash !== $row->data_hash;
    return ['changed' => $has_changed, 'hash' => $current_hash];
}

/**
 * Get migration statistics for a specific type
 * Calculates total items, migrated count, failed count, pending count, and progress percentage
 * 
 * @param string $type Migration type (users, products, categories)
 * @return array Statistics with keys: total, migrated, failed, pending, progress_percentage
 */
function mashry_connect_get_migration_stats($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $stats = array(
        'total' => 0,
        'migrated' => 0,
        'failed' => 0,
        'pending' => 0,
        'progress_percentage' => 0
    );
    
    // Get total items count based on migration type
    if ($type === 'users') {
        // Count all users in WordPress
        $stats['total'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        // Count users that have been successfully migrated
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    } elseif ($type === 'products') {
        // Count all WooCommerce products (post_type = 'product')
        $stats['total'] = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'"
        );
        // Count products that have been successfully migrated
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    } elseif ($type === 'categories') {
        // Count all product categories (taxonomy = 'product_cat')
        $stats['total'] = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->terms} t 
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             WHERE tt.taxonomy = 'product_cat'"
        );
        // Count categories that have been successfully migrated
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    }
    
    $stats['migrated'] = $migrated;
    
    // Count items that failed migration
    $stats['failed'] = (int)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'failed'",
            $type
        )
    ) ?: 0;
    
    // Calculate pending: total - migrated - failed
    $stats['pending'] = $stats['total'] - $stats['migrated'] - $stats['failed'];
    $stats['pending'] = max(0, $stats['pending']);
    
    // Calculate progress percentage
    if ($stats['total'] > 0) {
        $stats['progress_percentage'] = round(($stats['migrated'] / $stats['total']) * 100, 2);
    }
    
    return $stats;
}

/**
 * Start migration process
 * If force_export_all is true, sets option flag instead of deleting database
 * This preserves hash history for future incremental migrations
 * 
 * @param string $type Migration type (users, products, categories)
 * @param int $batch_size Items per batch (100, 500, or 1000)
 * @param bool $force_export_all Force re-export of all items without change detection
 * @return array Success response with total item count
 */
function mashry_connect_start_migration($type, $batch_size = 500, $force_export_all = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    // Ensure table exists and is up to date
    mashry_connect_create_tracking_table();
    
    if ($force_export_all) {
        // Instead of deleting database records (which destroys hash history),
        // set an option flag that will be checked in migrate_batch()
        // This allows change detection to work correctly after force export
        update_option('mashry_connect_force_export_all_' . $type, true);
    }
    
    // Get total count of items for this type
    $total = 0;
    
    if ($type === 'users') {
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    } elseif ($type === 'products') {
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'"
        );
    } elseif ($type === 'categories') {
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->terms} t 
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             WHERE tt.taxonomy = 'product_cat'"
        );
    }
    
    return [
        'success' => true,
        'total' => (int)$total,
        'batch_size' => $batch_size,
        'force_export_all' => $force_export_all
    ];
}

/**
 * Process a batch of items for migration
 * Implements change detection: only migrates items whose data has changed
 * If force_export_all flag is set, skips change detection and exports everything
 * Sends batch to server and saves local backup file
 * 
 * @param string $type Migration type (users, products, categories)
 * @param int $batch_number Current batch number (1-based)
 * @param int $batch_size Items per batch
 * @param bool $force_export_all Force re-export (deprecated - uses option flag instead)
 * @return array Response with migrated count, skipped count, items data, and has_more flag
 */
function mashry_connect_migrate_batch($type, $batch_number = 1, $batch_size = 500, $force_export_all = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $offset = ($batch_number - 1) * $batch_size;
    $items = [];
    $migrated_count = 0;
    $skipped_count = 0;
    
    // Check if force export all flag was set by start_migration()
    // This is checked via WordPress options instead of using database deletion
    $force_export_all = get_option('mashry_connect_force_export_all_' . $type, false);
    
    if ($type === 'users') {
        // Fetch batch of users from database
        $users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->users} ORDER BY ID ASC LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            )
        );
        
        foreach ($users as $user) {
            // Build user data array with all relevant fields
            $user_data = [
                'id' => (int)$user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'first_name' => get_user_meta($user->ID, 'first_name', true),
                'last_name' => get_user_meta($user->ID, 'last_name', true),
                'display_name' => $user->display_name,
                'registered_date' => $user->user_registered
            ];
            
            // Handle force export: skip change detection and export everything
            if ($force_export_all) {
                // Calculate hash directly without calling has_data_changed()
                $sorted_data = $user_data;
                ksort($sorted_data);
                $current_hash = hash('sha256', json_encode($sorted_data));
            } else {
                // Normal flow: check if data has changed using hash comparison
                $change_result = mashry_connect_has_data_changed($type, $user->ID, $user_data);
                
                // Skip this item if data hasn't changed since last export
                if (!$change_result['changed']) {
                    $skipped_count++;
                    continue;
                }
                
                // Use hash returned from has_data_changed() to avoid recalculation
                $current_hash = $change_result['hash'];
            }
            
            // Insert or update tracking record with current data and hash
            $result = $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO $table_name 
                    (migration_type, item_id, status, batch_number, migrated_data, data_hash, last_exported_at) 
                    VALUES (%s, %d, %s, %d, %s, %s, NOW())
                    ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    batch_number = VALUES(batch_number),
                    migrated_data = VALUES(migrated_data),
                    data_hash = VALUES(data_hash),
                    last_exported_at = NOW(),
                    updated_at = NOW()",
                    $type,
                    $user->ID,
                    'completed',
                    $batch_number,
                    json_encode($user_data),
                    $current_hash
                )
            );
            
            if ($result !== false) {
                $items[] = $user_data;
                $migrated_count++;
            }
        }
        
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $has_more = ($offset + $batch_size) < $total_users;
        
    } elseif ($type === 'products') {
        // Fetch batch of WooCommerce products
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->posts} 
                 WHERE post_type = 'product' 
                 ORDER BY ID ASC LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            )
        );
        
        foreach ($products as $product) {
            // Build product data array with all relevant fields
            $product_data = [
                'id' => (int)$product->ID,
                'name' => $product->post_title,
                'slug' => $product->post_name,
                'status' => $product->post_status,
                'type' => get_post_meta($product->ID, '_product_type', true) ?: 'simple',
                'sku' => get_post_meta($product->ID, '_sku', true),
                'price' => get_post_meta($product->ID, '_price', true),
                'regular_price' => get_post_meta($product->ID, '_regular_price', true),
                'description' => $product->post_content,
                'short_description' => $product->post_excerpt
            ];
            
            // Handle force export: skip change detection and export everything
            if ($force_export_all) {
                // Calculate hash directly without calling has_data_changed()
                $sorted_data = $product_data;
                ksort($sorted_data);
                $current_hash = hash('sha256', json_encode($sorted_data));
            } else {
                // Normal flow: check if data has changed using hash comparison
                $change_result = mashry_connect_has_data_changed($type, $product->ID, $product_data);
                
                // Skip this item if data hasn't changed since last export
                if (!$change_result['changed']) {
                    $skipped_count++;
                    continue;
                }
                
                // Use hash returned from has_data_changed() to avoid recalculation
                $current_hash = $change_result['hash'];
            }
            
            // Insert or update tracking record with current data and hash
            $result = $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO $table_name 
                    (migration_type, item_id, status, batch_number, migrated_data, data_hash, last_exported_at) 
                    VALUES (%s, %d, %s, %d, %s, %s, NOW())
                    ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    batch_number = VALUES(batch_number),
                    migrated_data = VALUES(migrated_data),
                    data_hash = VALUES(data_hash),
                    last_exported_at = NOW(),
                    updated_at = NOW()",
                    $type,
                    $product->ID,
                    'completed',
                    $batch_number,
                    json_encode($product_data),
                    $current_hash
                )
            );
            
            if ($result !== false) {
                $items[] = $product_data;
                $migrated_count++;
            }
        }
        
        $total_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'"
        );
        $has_more = ($offset + $batch_size) < $total_products;
        
    } elseif ($type === 'categories') {
        // Fetch batch of product categories
        $categories = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, tt.* 
                 FROM {$wpdb->terms} t 
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                 WHERE tt.taxonomy = 'product_cat' 
                 ORDER BY t.term_id ASC LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            )
        );
        
        foreach ($categories as $category) {
            // Build category data array with all relevant fields
            $category_data = [
                'id' => (int)$category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent' => (int)$category->parent
            ];
            
            // Handle force export: skip change detection and export everything
            if ($force_export_all) {
                // Calculate hash directly without calling has_data_changed()
                $sorted_data = $category_data;
                ksort($sorted_data);
                $current_hash = hash('sha256', json_encode($sorted_data));
            } else {
                // Normal flow: check if data has changed using hash comparison
                $change_result = mashry_connect_has_data_changed($type, $category->term_id, $category_data);
                
                // Skip this item if data hasn't changed since last export
                if (!$change_result['changed']) {
                    $skipped_count++;
                    continue;
                }
                
                // Use hash returned from has_data_changed() to avoid recalculation
                $current_hash = $change_result['hash'];
            }
            
            // Insert or update tracking record with current data and hash
            $result = $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO $table_name 
                    (migration_type, item_id, status, batch_number, migrated_data, data_hash, last_exported_at) 
                    VALUES (%s, %d, %s, %d, %s, %s, NOW())
                    ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    batch_number = VALUES(batch_number),
                    migrated_data = VALUES(migrated_data),
                    data_hash = VALUES(data_hash),
                    last_exported_at = NOW(),
                    updated_at = NOW()",
                    $type,
                    $category->term_id,
                    'completed',
                    $batch_number,
                    json_encode($category_data),
                    $current_hash
                )
            );
            
            if ($result !== false) {
                $items[] = $category_data;
                $migrated_count++;
            }
        }
        
        $total_categories = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->terms} t 
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             WHERE tt.taxonomy = 'product_cat'"
        );
        $has_more = ($offset + $batch_size) < $total_categories;
    }
    
    // Clean up force export all flag if this is the last batch
    // This prevents accidentally forcing exports on next migration
    if (!$has_more) {
        delete_option('mashry_connect_force_export_all_' . $type);
    }
    
    // Get total count of items that have been migrated
    $total_migrated = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT item_id) FROM $table_name WHERE migration_type = %s AND status = 'completed'",
            $type
        )
    ) ?: 0;
    
    // Get total items count based on type
    $total_items = 0;
    if ($type === 'users') {
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    } elseif ($type === 'products') {
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'");
    } elseif ($type === 'categories') {
        $total_items = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->terms} t 
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             WHERE tt.taxonomy = 'product_cat'"
        );
    }
    
    // Force empty items array if nothing was migrated
    if ($migrated_count === 0) {
        $items = [];
    }
    
    // Force stop if nothing was migrated in this batch and nothing was skipped
    // This prevents infinite loops when all items have been processed
    if ($migrated_count === 0 && $skipped_count === 0) {
        $has_more = false;
    }
    
    // Send batch to server after saving to database (if items were migrated)
    if (!empty($items) && $migrated_count > 0) {
        $send_result = mashry_connect_send_batch_to_server($type, $items, $batch_number);
        error_log('[Mashry Connect] Batch ' . $batch_number . ' - Migrated: ' . $migrated_count . ', Send to server: ' . json_encode($send_result));
    }
    
    // Return response with migration results
    return [
        'success' => true,
        $type . '_migrated' => $migrated_count,
        $type . '_skipped' => $skipped_count,
        $type . '_data' => $items,
        'total_migrated' => (int)$total_migrated,
        'total_items' => (int)$total_items,
        'has_more' => $has_more,
        'batch_number' => $batch_number
    ];
}

/**
 * Get current migration status for a type
 * Returns statistics and completion status
 * 
 * @param string $type Migration type (users, products, categories)
 * @return array Status response with stats and progress percentage
 */
function mashry_connect_get_migration_status($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $stats = mashry_connect_get_migration_stats($type);
    
    // Migration is completed when no pending or failed items remain
    $is_completed = $stats['pending'] === 0 && $stats['failed'] === 0;
    
    return [
        'success' => true,
        'stats' => $stats,
        'progress' => $stats['progress_percentage'],
        'is_completed' => $is_completed
    ];
}

/**
 * Reset migration by deleting all tracking records
 * This is different from force_export_all - this completely clears history
 * Use when you want to start fresh (not recommended for incremental migration)
 * 
 * @param string $type Migration type (users, products, categories)
 * @return array Success response
 */
function mashry_connect_reset_migration($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    // Delete all tracking records for this type
    // WARNING: This destroys hash history and breaks change detection
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE migration_type = %s",
            $type
        )
    );
    
    // Clean up any force export all flags
    delete_option('mashry_connect_force_export_all_' . $type);
    
    return [
        'success' => true,
        'message' => ucfirst($type) . ' migration reset successfully. All tracking data has been deleted.'
    ];
}