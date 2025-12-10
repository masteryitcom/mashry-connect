<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main REST API handler for users migration
 * Routes different actions to appropriate functions
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
            return rest_ensure_response(mashry_connect_start_migration('users', $batch_size, $force_restart));
            
        case 'migrate_batch':
            $batch_number = isset($params['batch']) ? (int)$params['batch'] : 1;
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            $force_restart = isset($params['force_restart']) ? (bool)$params['force_restart'] : false;
            return rest_ensure_response(mashry_connect_migrate_batch('users', $batch_number, $batch_size, $force_restart));
            
        case 'migration_status':
            return rest_ensure_response(mashry_connect_get_migration_status('users'));
            
        case 'reset_migration':
            return rest_ensure_response(mashry_connect_reset_migration('users'));
            
        case 'get_all':
            return mashry_connect_get_all_users();
            
        default:
            return new WP_Error('invalid_action', 'Invalid action specified', ['status' => 400]);
    }
}

/**
 * Preview users - get stats and sample data
 * Shows first 5 users with all their relevant fields
 * Used for UI preview before migration starts
 */
function mashry_connect_preview_users() {
    global $wpdb;
    
    // Get migration statistics for users
    $stats = mashry_connect_get_migration_stats('users');
    
    // Get first 5 users for preview
    $users = $wpdb->get_results(
        "SELECT ID, user_login, user_email, display_name, user_registered 
         FROM {$wpdb->users} 
         WHERE ID > 0
         ORDER BY ID ASC 
         LIMIT 5"
    );
    
    $sample = [];
    if ($users) {
        foreach ($users as $user) {
            // Debug: Make sure data is not empty
            $username = !empty($user->user_login) ? $user->user_login : 'Anonymous';
            $email = !empty($user->user_email) ? $user->user_email : 'no-email@example.com';
            $display = !empty($user->display_name) ? $user->display_name : $username;
            
            $sample[] = [
                'id' => (int)$user->ID,
                'username' => $username,
                'email' => $email,
                'display_name' => $display,
                'user_registered' => $user->user_registered
            ];
        }
    }
    
    // Add sample data to stats
    $stats['sample'] = $sample;
    
    return rest_ensure_response($stats);
}

/**
 * Get all users data - complete export without batching
 * Used for "Download All" button
 * Exports all users with complete information including roles and meta
 */
function mashry_connect_get_all_users() {
    global $wpdb;
    
    // Get all users ordered by ID
    $users = $wpdb->get_results("SELECT * FROM {$wpdb->users} ORDER BY ID ASC");
    
    $users_data = [];
    
    foreach ($users as $user) {
        // Build complete user data array
        $user_data = [
            'id' => (int)$user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'display_name' => $user->display_name,
            'nickname' => get_user_meta($user->ID, 'nickname', true),
            'website' => $user->user_url,
            'registered_date' => $user->user_registered,
            'roles' => []
        ];
        
        // Get user roles
        $user_info = get_userdata($user->ID);
        if ($user_info) {
            $user_data['roles'] = (array)$user_info->roles;
        }
        
        $users_data[] = $user_data;
    }
    
    return rest_ensure_response($users_data);
}