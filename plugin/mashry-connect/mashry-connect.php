<?php
/**
 * Plugin Name: mashry Connect
 * Description: Connect your WordPress with your mashry Store
 * Version:     1.0.0
 * Author:      Ahmed Salah
 * License:     GPLv2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define API Key
define('MASHRY_CONNECT_API_KEY', 'mashry-secret-static-key-here-123');

// Include required files - helpers must be loaded first
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/migration-tracking.php';
require_once plugin_dir_path(__FILE__) . 'includes/users-export.php';
require_once plugin_dir_path(__FILE__) . 'includes/products-export.php';
require_once plugin_dir_path(__FILE__) . 'includes/categories-export.php';

// Create table on activation
register_activation_hook(__FILE__, 'mashry_connect_create_tracking_table');

// Check and create table if doesn't exist
add_action('plugins_loaded', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        mashry_connect_create_tracking_table();
    }
});

// Add admin menu
add_action('admin_menu', function() {
    add_options_page(
        'mashry Connect Settings',
        'mashry Connect',
        'manage_options',
        'mashry-connect-settings',
        'mashry_connect_render_settings_page'
    );
});

// Register REST API routes
add_action('rest_api_init', function() {
    // Users endpoints
    register_rest_route('mashry-connect/v1', '/export/users', [
        'methods' => 'GET',
        'callback' => 'mashry_connect_export_users',
        'permission_callback' => function($request) {
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
                $key = substr($auth_header, 7);
                return $key === MASHRY_CONNECT_API_KEY;
            }
            return false;
        },
        'args' => [
            'action' => [
                'required' => false,
                'default' => 'preview',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'batch_size' => [
                'required' => false,
                'default' => 500,
                'type' => 'integer'
            ],
            'batch' => [
                'required' => false,
                'default' => 1,
                'type' => 'integer'
            ],
            'force_restart' => [
                'required' => false,
                'default' => false,
                'type' => 'boolean'
            ]
        ]
    ]);
    
    // Products endpoints
    register_rest_route('mashry-connect/v1', '/export/products', [
        'methods' => 'GET',
        'callback' => 'mashry_connect_export_products',
        'permission_callback' => function($request) {
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
                $key = substr($auth_header, 7);
                return $key === MASHRY_CONNECT_API_KEY;
            }
            return false;
        },
        'args' => [
            'action' => [
                'required' => false,
                'default' => 'preview',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'batch_size' => [
                'required' => false,
                'default' => 500,
                'type' => 'integer'
            ],
            'batch' => [
                'required' => false,
                'default' => 1,
                'type' => 'integer'
            ],
            'force_restart' => [
                'required' => false,
                'default' => false,
                'type' => 'boolean'
            ]
        ]
    ]);
    
    // Categories endpoints
    register_rest_route('mashry-connect/v1', '/export/categories', [
        'methods' => 'GET',
        'callback' => 'mashry_connect_export_categories',
        'permission_callback' => function($request) {
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
                $key = substr($auth_header, 7);
                return $key === MASHRY_CONNECT_API_KEY;
            }
            return false;
        },
        'args' => [
            'action' => [
                'required' => false,
                'default' => 'preview',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'batch_size' => [
                'required' => false,
                'default' => 500,
                'type' => 'integer'
            ],
            'batch' => [
                'required' => false,
                'default' => 1,
                'type' => 'integer'
            ],
            'force_restart' => [
                'required' => false,
                'default' => false,
                'type' => 'boolean'
            ]
        ]
    ]);
    
    // Test server endpoint
    register_rest_route('mashry-connect/v1', '/test-server', [
        'methods' => 'GET',
        'callback' => 'mashry_connect_test_server',
        'permission_callback' => function($request) {
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
                $key = substr($auth_header, 7);
                return $key === MASHRY_CONNECT_API_KEY;
            }
            return false;
        },
        'args' => [
            'server_url' => [
                'required' => true,
                'sanitize_callback' => 'esc_url_raw'
            ]
        ]
    ]);
});
// AJAX handler for saving server settings
add_action('wp_ajax_mashry_save_server_settings', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $server_url = sanitize_text_field($_POST['server_url']);
    update_option('mashry_node_server_url', $server_url);
    
    wp_send_json_success(['message' => 'Settings saved']);
});

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=mashry-connect-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', function($page) {
    // Only on our settings page
    if ($page !== 'settings_page_mashry-connect-settings') {
        return;
    }
    
    // Enqueue jQuery
    wp_enqueue_script('jquery');
    
    // Enqueue CSS
    wp_enqueue_style(
        'mashry-connect-admin',
        plugin_dir_url(__FILE__) . 'assets/css/admin.css',
        [],
        '1.0.0'
    );
    
    // Enqueue JavaScript
    wp_enqueue_script(
        'mashry-connect-admin',
        plugin_dir_url(__FILE__) . 'assets/js/admin.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    // Localize script with data
    wp_localize_script(
        'mashry-connect-admin',
        'mashryConnect',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('mashry-connect/v1/'),
            'api_key' => MASHRY_CONNECT_API_KEY,
            'nonce' => wp_create_nonce('mashry-connect-nonce')
        ]
    );
});

// Test server connection
function mashry_connect_test_server(WP_REST_Request $request) {
    $server_url = $request->get_param('server_url');
    
    if (empty($server_url)) {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Server URL is required'
        ]);
    }
    
    $response = wp_remote_get($server_url, ['timeout' => 5]);
    
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
            'message' => "Server responded with status: $status_code"
        ]);
    }
}

// Include admin pages
require_once plugin_dir_path(__FILE__) . 'admin-pages.php';