<?php
// API Routes
if (!defined('ABSPATH')) {
    exit;
}

function mashry_connect_register_api_route() {
    // Products endpoints
    register_rest_route("mashry-connect/v1", "/export/products", [
        "methods" => "GET",
        "callback" => "mashry_connect_export_products",
        "permission_callback" => "mashry_connect_check_api_key",
        'args' => [
            'action' => [
                'required' => false,
                'default' => 'preview',
                'validate_callback' => function($param) {
                    return in_array($param, ['preview', 'start_migration', 'migrate_batch', 'migration_status', 'reset_migration', 'get_all']);
                }
            ]
        ]
    ]);
    
    // Users endpoints
    register_rest_route("mashry-connect/v1", "/export/users", [
        "methods" => "GET",
        "callback" => "mashry_connect_export_users",
        "permission_callback" => "mashry_connect_check_api_key",
        'args' => [
            'action' => [
                'required' => false,
                'default' => 'preview',
                'validate_callback' => function($param) {
                    return in_array($param, ['preview', 'start_migration', 'migrate_batch', 'migration_status', 'reset_migration', 'get_all']);
                }
            ]
        ]
    ]);
    
    // Categories endpoints
    register_rest_route("mashry-connect/v1", "/export/categories", [
        "methods" => "GET",
        "callback" => "mashry_connect_export_categories",
        "permission_callback" => "mashry_connect_check_api_key",
        'args' => [
            'action' => [
                'required' => false,
                'default' => 'preview',
                'validate_callback' => function($param) {
                    return in_array($param, ['preview', 'start_migration', 'migrate_batch', 'migration_status', 'reset_migration', 'get_all']);
                }
            ]
        ]
    ]);
    
    // Test server endpoint
    register_rest_route("mashry-connect/v1", "/test-server", [
        "methods" => "GET",
        "callback" => "mashry_connect_test_server",
        "permission_callback" => "mashry_connect_check_api_key",
        'args' => [
            'server_url' => [
                'required' => true,
                'sanitize_callback' => 'esc_url_raw'
            ]
        ]
    ]);
}

function mashry_connect_test_server(WP_REST_Request $request) {
    $server_url = $request->get_param('server_url');
    
    if (empty($server_url)) {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Server URL is required'
        ]);
    }
    
    $response = wp_remote_get($server_url, [
        'timeout' => 10
    ]);
    
    if (is_wp_error($response)) {
        return rest_ensure_response([
            'success' => false,
            'message' => $response->get_error_message()
        ]);
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code === 200) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Server is responding'
        ]);
    } else {
        return rest_ensure_response([
            'success' => false,
            'message' => "Server responded with status: {$status_code}"
        ]);
    }
}