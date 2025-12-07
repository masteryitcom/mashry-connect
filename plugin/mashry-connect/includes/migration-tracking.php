<?php
// migration-tracking.php

if (!defined('ABSPATH')) {
    exit;
}

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
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY migration_type (migration_type),
        KEY item_id (item_id),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function mashry_connect_start_migration($type, $batch_size = 500, $force_restart = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    if ($force_restart) {
        $wpdb->delete($table_name, ['migration_type' => $type]);
    }
    
    // Get total count
    $total = 0;
    if ($type === 'users') {
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    } elseif ($type === 'products') {
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                'product'
            )
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
        'batch_size' => $batch_size
    ];
}

function mashry_connect_migrate_batch($type, $batch_number = 1, $batch_size = 500) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $offset = ($batch_number - 1) * $batch_size;
    $items = [];
    $migrated_count = 0;
    
    if ($type === 'users') {
        $users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->users} ORDER BY ID ASC LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            )
        );
        
        foreach ($users as $user) {
            $already_migrated = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE migration_type = %s AND item_id = %d AND status = 'completed'",
                    $type,
                    $user->ID
                )
            );
            
            if (!$already_migrated) {
                $user_data = [
                    'id' => (int)$user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'first_name' => get_user_meta($user->ID, 'first_name', true),
                    'last_name' => get_user_meta($user->ID, 'last_name', true),
                    'display_name' => $user->display_name,
                    'registered_date' => $user->user_registered
                ];
                
                $wpdb->insert($table_name, [
                    'migration_type' => $type,
                    'item_id' => $user->ID,
                    'status' => 'completed',
                    'batch_number' => $batch_number,
                    'migrated_data' => json_encode($user_data)
                ]);
                
                $items[] = $user_data;
                $migrated_count++;
            }
        }
        
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $has_more = ($offset + $batch_size) < $total_users;
        
    } elseif ($type === 'products') {
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
            $already_migrated = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE migration_type = %s AND item_id = %d AND status = 'completed'",
                    $type,
                    $product->ID
                )
            );
            
            if (!$already_migrated) {
                $product_data = [
                    'id' => (int)$product->ID,
                    'name' => $product->post_title,
                    'slug' => $product->post_name,
                    'status' => $product->post_status,
                    'type' => get_post_meta($product->ID, '_product_type', true) ?: 'simple',
                    'sku' => get_post_meta($product->ID, '_sku', true),
                    'price' => get_post_meta($product->ID, '_price', true)
                ];
                
                $wpdb->insert($table_name, [
                    'migration_type' => $type,
                    'item_id' => $product->ID,
                    'status' => 'completed',
                    'batch_number' => $batch_number,
                    'migrated_data' => json_encode($product_data)
                ]);
                
                $items[] = $product_data;
                $migrated_count++;
            }
        }
        
        $total_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'"
        );
        $has_more = ($offset + $batch_size) < $total_products;
        
    } elseif ($type === 'categories') {
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
            $already_migrated = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE migration_type = %s AND item_id = %d AND status = 'completed'",
                    $type,
                    $category->term_id
                )
            );
            
            if (!$already_migrated) {
                $category_data = [
                    'id' => (int)$category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent' => (int)$category->parent
                ];
                
                $wpdb->insert($table_name, [
                    'migration_type' => $type,
                    'item_id' => $category->term_id,
                    'status' => 'completed',
                    'batch_number' => $batch_number,
                    'migrated_data' => json_encode($category_data)
                ]);
                
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
    
    $total_migrated = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT item_id) FROM $table_name WHERE migration_type = %s AND status = 'completed'",
            $type
        )
    ) ?: 0;
    
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
    
    return [
        'success' => true,
        $type . '_migrated' => $migrated_count,
        $type . '_data' => $items,
        'total_migrated' => (int)$total_migrated,
        'total_items' => (int)$total_items,
        'has_more' => $has_more,
        'batch_number' => $batch_number
    ];
}

function mashry_connect_get_migration_status($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $stats = mashry_connect_get_migration_stats($type);
    
    $is_completed = ($stats['migrated'] + $stats['failed']) >= $stats['total'];
    $progress = $stats['total'] > 0 ? round(($stats['migrated'] / $stats['total']) * 100, 2) : 0;
    
    return [
        'success' => true,
        'stats' => $stats,
        'is_completed' => $is_completed,
        'progress' => $progress
    ];
}

function mashry_connect_get_migration_stats($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $stats = [
        'total' => 0,
        'migrated' => 0,
        'failed' => 0,
        'pending' => 0,
        'progress_percentage' => 0
    ];
    
    if ($type === 'users') {
        $stats['total'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM $table_name WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    } elseif ($type === 'products') {
        $stats['total'] = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                'product'
            )
        );
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM $table_name WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    } elseif ($type === 'categories') {
        $stats['total'] = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->terms} t 
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             WHERE tt.taxonomy = 'product_cat'"
        );
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM $table_name WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    }
    
    $stats['migrated'] = $migrated;
    $stats['failed'] = (int)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT item_id) FROM $table_name WHERE migration_type = %s AND status = 'failed'",
            $type
        )
    ) ?: 0;
    $stats['pending'] = $stats['total'] - $stats['migrated'] - $stats['failed'];
    $stats['pending'] = max(0, $stats['pending']);
    
    if ($stats['total'] > 0) {
        $stats['progress_percentage'] = round(($stats['migrated'] / $stats['total']) * 100, 2);
    }
    
    return $stats;
}

function mashry_connect_reset_migration($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $deleted = $wpdb->delete($table_name, ['migration_type' => $type]);
    
    return [
        'success' => true,
        'message' => 'Migration reset successfully',
        'deleted_count' => $deleted
    ];
}