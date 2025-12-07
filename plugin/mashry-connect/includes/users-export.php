<?php
// users-export.php

if (!defined('ABSPATH')) {
    exit;
}

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
            return rest_ensure_response(mashry_connect_migrate_batch('users', $batch_number, $batch_size));
            
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

function mashry_connect_preview_users() {
    global $wpdb;
    
    $stats = mashry_connect_get_migration_stats('users');
    
    // Get last 10 users for preview
    $users = $wpdb->get_results(
        "SELECT ID, user_login, user_email, display_name, user_registered 
         FROM {$wpdb->users} 
         ORDER BY ID DESC 
         LIMIT 10"
    );
    
    $sample = [];
    foreach ($users as $user) {
        $sample[] = [
            'id' => $user->ID,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name,
            'user_registered' => $user->user_registered
        ];
    }
    
    $stats['sample'] = $sample;
    
    return rest_ensure_response($stats);
}

function mashry_connect_get_all_users() {
    global $wpdb;
    
    $users = $wpdb->get_results("SELECT * FROM {$wpdb->users} ORDER BY ID ASC");
    
    $users_data = [];
    
    foreach ($users as $user) {
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
        
        $user_info = get_userdata($user->ID);
        if ($user_info) {
            $user_data['roles'] = $user_info->roles;
        }
        
        $users_data[] = $user_data;
    }
    
    return rest_ensure_response($users_data);
}