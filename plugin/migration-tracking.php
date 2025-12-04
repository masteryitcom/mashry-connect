<?php
// File: migration-tracking.php

/**
 * Create migration tracking table
 */
function mashry_connect_create_tracking_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        migration_type varchar(50) NOT NULL,
        item_id bigint(20) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        batch_number int(11) DEFAULT 0,
        migrated_at datetime DEFAULT CURRENT_TIMESTAMP,
        error_message text,
        PRIMARY KEY (id),
        KEY migration_type (migration_type),
        KEY status (status),
        KEY item_id (item_id),
        KEY migration_type_item (migration_type, item_id),
        KEY migration_type_status (migration_type, status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Get migration statistics for any type
 */
function mashry_connect_get_migration_stats($type = 'products') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $stats = array(
        'total' => 0,
        'migrated' => 0,
        'pending' => 0,
        'failed' => 0,
        'progress_percentage' => 0,
        'sample' => array()
    );
    
    // Get total count from source based on type
    if ($type === 'products') {
        $stats['total'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
            AND post_status IN ('publish', 'draft', 'private')"
        );
        
        // Get sample of products
        $sample_results = $wpdb->get_results(
            "SELECT ID, post_title, post_status 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
            AND post_status IN ('publish', 'draft', 'private')
            ORDER BY ID ASC
            LIMIT 10"
        );
        
        foreach ($sample_results as $item) {
            $stats['sample'][] = array(
                'id' => $item->ID,
                'name' => $item->post_title,
                'status' => $item->post_status,
                'edit_url' => admin_url('post.php?post=' . $item->ID . '&action=edit')
            );
        }
        
    } elseif ($type === 'users') {
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        
        // Get sample of users
        $sample_results = $wpdb->get_results(
            "SELECT ID, user_login, user_email, display_name 
            FROM {$wpdb->users} 
            ORDER BY ID ASC
            LIMIT 10"
        );
        
        foreach ($sample_results as $item) {
            $stats['sample'][] = array(
                'id' => $item->ID,
                'user_login' => $item->user_login,
                'user_email' => $item->user_email,
                'display_name' => $item->display_name
            );
        }
        
    } elseif ($type === 'categories') {
        $stats['total'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->terms} 
            WHERE term_id IN (
                SELECT term_id FROM {$wpdb->term_taxonomy} 
                WHERE taxonomy = 'product_cat'
            )"
        );
        
        // Get sample of categories
        $sample_results = $wpdb->get_results(
            "SELECT t.term_id, t.name, t.slug, tt.description 
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_cat'
            ORDER BY t.term_id ASC
            LIMIT 10"
        );
        
        foreach ($sample_results as $item) {
            $stats['sample'][] = array(
                'id' => $item->term_id,
                'name' => $item->name,
                'slug' => $item->slug,
                'description' => $item->description
            );
        }
    }
    
    // Get migration tracking stats - count DISTINCT items
    $tracking_stats = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT CASE WHEN status = 'completed' THEN item_id END) as migrated,
                COUNT(DISTINCT CASE WHEN status = 'pending' THEN item_id END) as pending,
                COUNT(DISTINCT CASE WHEN status = 'failed' THEN item_id END) as failed
            FROM $table_name 
            WHERE migration_type = %s",
            $type
        )
    );
    
    if ($tracking_stats) {
        $stats['migrated'] = (int)$tracking_stats->migrated;
        $stats['pending'] = (int)$tracking_stats->pending;
        $stats['failed'] = (int)$tracking_stats->failed;
    }
    
    // Calculate pending (not migrated yet)
    $stats['pending'] = max(0, $stats['total'] - $stats['migrated']);
    
    // Calculate progress percentage
    $stats['progress_percentage'] = $stats['total'] > 0 ? round(($stats['migrated'] / $stats['total']) * 100, 2) : 0;
    
    return $stats;
}

/**
 * Get last processed offset for a migration type
 */
function mashry_connect_get_migration_offset($type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    return (int)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT item_id) FROM $table_name 
            WHERE migration_type = %s AND status = 'completed'",
            $type
        )
    );
}

/**
 * Check if item is already migrated
 */
function mashry_connect_is_item_migrated($type, $item_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    return (bool)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT item_id) FROM $table_name 
            WHERE migration_type = %s AND item_id = %d AND status = 'completed'",
            $type, $item_id
        )
    );
}

/**
 * Mark item as migrated
 */
function mashry_connect_mark_item_migrated($type, $item_id, $batch_number = 1, $error = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $data = array(
        'migration_type' => $type,
        'item_id' => $item_id,
        'batch_number' => $batch_number,
        'migrated_at' => current_time('mysql')
    );
    
    if ($error) {
        $data['status'] = 'failed';
        $data['error_message'] = $error;
    } else {
        $data['status'] = 'completed';
    }
    
    return $wpdb->insert($table_name, $data);
}