<?php
// File: helpers.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug log function
 */
function mashry_connect_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[Mashry Connect] ' . $message;
        if ($data !== null) {
            $log_message .= ': ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

/**
 * Get migration statistics
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
    
    // Get total items count based on type
    if ($type === 'users') {
        $stats['total'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'completed'",
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
                "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    } elseif ($type === 'categories') {
        $stats['total'] = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->terms} WHERE term_id IN (SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s)",
                'product_cat'
            )
        );
        $migrated = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'completed'",
                $type
            )
        );
    }
    
    $stats['migrated'] = $migrated;
    $stats['failed'] = (int)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT item_id) FROM {$table_name} WHERE migration_type = %s AND status = 'failed'",
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