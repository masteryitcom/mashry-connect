<?php
// File: users-export.php

/**
 * Export users with pagination/chunking support
 */
function mashry_connect_export_users(WP_REST_Request $request) {
    $params = $request->get_params();
    $action = isset($params['action']) ? sanitize_text_field($params['action']) : 'preview';
    
    switch ($action) {
        case 'preview':
            return mashry_connect_preview_users();
            
        case 'start_migration':
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            $force_restart = isset($params['force_restart']) ? (bool)$params['force_restart'] : false;
            return mashry_connect_start_migration('users', $batch_size, $force_restart);
            
        case 'migrate_batch':
            $batch_number = isset($params['batch']) ? (int)$params['batch'] : 1;
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            return mashry_connect_migrate_batch('users', $batch_number, $batch_size);
            
        case 'migration_status':
            return mashry_connect_get_migration_status('users');
            
        case 'reset_migration':
            return mashry_connect_reset_migration('users');
            
        case 'get_all':
            return mashry_connect_get_all_users();
            
        default:
            return new WP_Error('invalid_action', 'Invalid action specified', array('status' => 400));
    }
}

/**
 * Preview users before migration
 */
function mashry_connect_preview_users() {
    $stats = mashry_connect_get_migration_stats('users');
    return rest_ensure_response($stats);
}

/**
 * Get all users (original function)
 */
function mashry_connect_get_all_users() {
    global $wpdb;
    
    $users = $wpdb->get_results("SELECT * FROM {$wpdb->users} ORDER BY ID ASC");
    
    $users_data = array();
    
    foreach ($users as $user) {
        $user_data = mashry_connect_prepare_user_data($user);
        if ($user_data) {
            $users_data[] = $user_data;
        }
    }
    
    return rest_ensure_response($users_data);
}