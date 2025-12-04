<?php
/**
 * Plugin Name: mashry Connect
 * Description: Connect your WordPress with your mashry Store
 * Version:     1.0.0
 * Author:      Ahmed Salah
 * License:     GPLv2 or later
 * Text Domain: mashry-connect
 */
define("MASHRY_CONNECT_API_KEY", "mashry-secret-static-key-here-123");

// Include all required files
require_once plugin_dir_path(__FILE__) . "migration-tracking.php";
require_once plugin_dir_path(__FILE__) . "users-export.php";
require_once plugin_dir_path(__FILE__) . "products-export.php";
require_once plugin_dir_path(__FILE__) . "categories-export.php";

// Exit if accessed directly.
if (!defined("ABSPATH")) {
    exit();
}

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

/**
 * Adds a new submenu item to the "Settings" menu.
 */
function mashry_connect_add_settings_page()
{
    add_options_page(
        "mashry Connect Settings", // Page title
        "mashry Connect", // Menu title
        "manage_options", // Capability required to access the page
        "mashry-connect-settings", // Unique menu slug
        "mashry_connect_render_settings_page", // Callback function to render the page content
    );
}

/**
 * Registers the custom REST API endpoint for users with a key.
 */
function mashry_connect_register_api_route()
{
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
            ],
            'batch' => [
                'required' => false,
                'default' => 1,
                'sanitize_callback' => 'absint'
            ],
            'batch_size' => [
                'required' => false,
                'default' => 500,
                'sanitize_callback' => 'absint'
            ],
            'force_restart' => [
                'required' => false,
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
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
            ],
            'batch' => [
                'required' => false,
                'default' => 1,
                'sanitize_callback' => 'absint'
            ],
            'batch_size' => [
                'required' => false,
                'default' => 500,
                'sanitize_callback' => 'absint'
            ],
            'force_restart' => [
                'required' => false,
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
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
            ],
            'batch' => [
                'required' => false,
                'default' => 1,
                'sanitize_callback' => 'absint'
            ],
            'batch_size' => [
                'required' => false,
                'default' => 500,
                'sanitize_callback' => 'absint'
            ],
            'force_restart' => [
                'required' => false,
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
            ]
        ]
    ]);
    
    // Test server endpoint
    register_rest_route("mashry-connect/v1", "/test-server", [
        "methods" => "GET",
        "callback" => "mashry_connect_test_server",
        "permission_callback" => "mashry_connect_check_api_key",
    ]);
}

/**
 * Renders the content for the settings page.
 */
function mashry_connect_render_settings_page()
{
    if (!current_user_can("manage_options")) {
        return;
    }

    // Get saved Node.js server URL
    $node_server_url = get_option('mashry_node_server_url', 'http://localhost:5000');
    ?>

    <div class="wrap">
        <h1>mashry Connect</h1>
        
        <style>
            .settings-form {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                margin-top: 30px;
                max-width: 600px;
                border-radius: 5px;
            }
            .settings-form input {
                width: 100%;
                padding: 10px;
                margin: 5px 0 15px 0;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .migration-container {
                margin-top: 40px; 
                padding: 25px; 
                background: #fff; 
                border-radius: 5px;
                border: 1px solid #ccd0d4;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .migration-log {
                max-height: 300px; 
                overflow-y: auto; 
                background: white; 
                padding: 15px; 
                margin-top: 20px; 
                border-radius: 5px; 
                display: none;
                border: 1px solid #dee2e6;
            }
            .tab-container {
                margin: 20px 0;
            }
            .tab-buttons {
                display: flex;
                border-bottom: 2px solid #dee2e6;
                margin-bottom: -1px;
            }
            .tab-button {
                padding: 12px 24px;
                background: #f8f9fa;
                border: none;
                border-bottom: 3px solid transparent;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #495057;
                transition: all 0.3s;
            }
            .tab-button:hover {
                background: #e9ecef;
                color: #495057;
            }
            .tab-button.active {
                background: white;
                border-bottom-color: #2271b1;
                font-weight: 600;
                color: #2271b1;
            }
            .tab-content {
                display: none;
                padding: 25px;
                background: white;
                border: 1px solid #dee2e6;
                border-top: none;
                border-radius: 0 0 5px 5px;
            }
            .tab-content.active {
                display: block;
            }
            .btn {
                padding: 10px 20px;
                background: #2271b1;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin: 5px;
                font-size: 14px;
                font-weight: 500;
                transition: background 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .btn:hover {
                background: #135e96;
                color: white;
            }
            .btn:disabled {
                background: #cccccc;
                cursor: not-allowed;
            }
            .btn-secondary {
                background: #6c757d;
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
            .btn-success {
                background: #28a745;
            }
            .btn-success:hover {
                background: #218838;
            }
            .btn-danger {
                background: #dc3545;
            }
            .btn-danger:hover {
                background: #c82333;
            }
            .btn-warning {
                background: #ffc107;
                color: #212529;
            }
            .btn-warning:hover {
                background: #e0a800;
                color: #212529;
            }
            .preview-box {
                background: #f8f9fa;
                padding: 20px;
                margin: 15px 0;
                border-radius: 5px;
                border: 1px solid #dee2e6;
            }
            .preview-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                font-size: 14px;
                background: white;
            }
            .preview-table th {
                background: #f8f9fa;
                padding: 12px;
                border: 1px solid #dee2e6;
                text-align: left;
                font-weight: 600;
                color: #495057;
            }
            .preview-table td {
                padding: 10px;
                border: 1px solid #dee2e6;
                vertical-align: middle;
                color: #212529;
            }
            .preview-table tr:hover {
                background-color: #f8f9fa;
            }
            .stats-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 25px;
                background: white;
            }
            .stats-table td {
                padding: 12px;
                border: 1px solid #dee2e6;
            }
            .stats-table td:first-child {
                background: #f8f9fa;
                font-weight: 600;
                width: 200px;
                color: #495057;
            }
            .progress-container {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .progress-bar-bg {
                flex: 1;
                background: #e9ecef;
                height: 10px;
                border-radius: 5px;
                overflow: hidden;
            }
            .progress-bar-fill {
                height: 100%;
                background: #28a745;
                border-radius: 5px;
                transition: width 0.3s ease;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-publish {
                background: #d4edda;
                color: #155724;
            }
            .status-draft {
                background: #f8f9fa;
                color: #6c757d;
            }
            .status-private {
                background: #d1ecf1;
                color: #0c5460;
            }
            .view-link {
                color: #007bff;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-weight: 500;
            }
            .view-link:hover {
                color: #0056b3;
                text-decoration: underline;
            }
            .control-group {
                display: flex;
                align-items: center;
                gap: 20px;
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 5px;
                border: 1px solid #dee2e6;
            }
            .control-label {
                font-weight: 600;
                margin-bottom: 8px;
                display: block;
                color: #495057;
            }
            select {
                padding: 10px 15px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                background: white;
                font-size: 14px;
                min-width: 200px;
            }
            .button-group {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .section-title {
                color: #2271b1;
                border-bottom: 2px solid #2271b1;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .log-entry {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
                display: flex;
                align-items: flex-start;
                gap: 10px;
            }
            .log-time {
                color: #6c757d;
                font-size: 12px;
                min-width: 85px;
            }
            .log-message {
                flex: 1;
            }
            .log-info { color: #17a2b8; }
            .log-success { color: #28a745; }
            .log-warning { color: #ffc107; }
            .log-error { color: #dc3545; }
            .server-test-result {
                margin-top: 15px;
                padding: 12px;
                border-radius: 5px;
                display: none;
            }
            .server-test-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .server-test-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .file-downloads {
                margin-top: 20px;
                padding: 15px;
                background: #e9ecef;
                border-radius: 5px;
                border: 1px solid #dee2e6;
            }
            .download-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                background: white;
                margin-bottom: 8px;
                border-radius: 4px;
                border: 1px solid #dee2e6;
            }
            .download-filename {
                font-weight: 500;
                color: #495057;
            }
            .download-size {
                color: #6c757d;
                font-size: 12px;
            }
            .download-link {
                color: #007bff;
                text-decoration: none;
                font-weight: 500;
            }
            .download-link:hover {
                text-decoration: underline;
            }
        </style>

        <div class="settings-form">
            <h3 style="margin-top: 0;">Server Settings</h3>
            <form id="server-settings-form">
                <label for="server-url" class="control-label">Server URL:</label>
                <input type="url" id="server-url" name="server_url" 
                       value="<?php echo esc_attr($node_server_url); ?>" 
                       placeholder="http://localhost:5000">
                
                <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
                    <button type="submit" class="btn">
                        💾 Save Settings
                    </button>
                    <button type="button" onclick="testServerConnection()" class="btn btn-secondary">
                        🧪 Test Connection
                    </button>
                    <span id="settings-result" style="margin-left: 10px;"></span>
                </div>
                
                <div id="server-test-result" class="server-test-result"></div>
            </form>
        </div>

        <div class="migration-container">
            <h2 style="color: #2271b1; margin-top: 0;">📦 Migration with Batch Processing</h2>
            
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-button active" onclick="showTab('products')">
                        📦 Products
                    </button>
                    <button class="tab-button" onclick="showTab('users')">
                        👥 Users
                    </button>
                    <button class="tab-button" onclick="showTab('categories')">
                        📂 Categories
                    </button>
                    <button class="tab-button" onclick="showTab('downloads')">
                        📥 Downloads
                    </button>
                </div>
                
                <!-- Products Tab -->
                <div id="tab-products" class="tab-content active">
                    <div class="preview-box">
                        <h3 class="section-title">Products Migration Preview</h3>
                        <div id="preview-content-products"></div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button onclick="loadMigrationPreview('products')" class="btn">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <div>
                            <label class="control-label">Batch Size:</label>
                            <select id="batch-size-products">
                                <option value="100">100 products per batch</option>
                                <option value="500" selected>500 products per batch</option>
                                <option value="1000">1000 products per batch</option>
                            </select>
                        </div>
                        
                        <div class="button-group">
                            <button onclick="startMigration('products')" class="btn btn-success" id="btn-start-migration-products">
                                🚀 Start Migration
                            </button>
                            
                            <button onclick="checkMigrationStatus('products')" class="btn" id="btn-check-status-products">
                                📊 Check Status
                            </button>
                            
                            <button onclick="resetMigration('products')" class="btn btn-danger" id="btn-reset-migration-products">
                                🔄 Reset Migration
                            </button>
                            
                            <button onclick="downloadAllData('products')" class="btn btn-warning">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Users Tab -->
                <div id="tab-users" class="tab-content">
                    <div class="preview-box">
                        <h3 class="section-title">Users Migration Preview</h3>
                        <div id="preview-content-users"></div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button onclick="loadMigrationPreview('users')" class="btn">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <div>
                            <label class="control-label">Batch Size:</label>
                            <select id="batch-size-users">
                                <option value="100">100 users per batch</option>
                                <option value="500" selected>500 users per batch</option>
                                <option value="1000">1000 users per batch</option>
                            </select>
                        </div>
                        
                        <div class="button-group">
                            <button onclick="startMigration('users')" class="btn btn-success" id="btn-start-migration-users">
                                🚀 Start Migration
                            </button>
                            
                            <button onclick="checkMigrationStatus('users')" class="btn" id="btn-check-status-users">
                                📊 Check Status
                            </button>
                            
                            <button onclick="resetMigration('users')" class="btn btn-danger" id="btn-reset-migration-users">
                                🔄 Reset Migration
                            </button>
                            
                            <button onclick="downloadAllData('users')" class="btn btn-warning">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Categories Tab -->
                <div id="tab-categories" class="tab-content">
                    <div class="preview-box">
                        <h3 class="section-title">Categories Migration Preview</h3>
                        <div id="preview-content-categories"></div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button onclick="loadMigrationPreview('categories')" class="btn">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <div>
                            <label class="control-label">Batch Size:</label>
                            <select id="batch-size-categories">
                                <option value="100">100 categories per batch</option>
                                <option value="500" selected>500 categories per batch</option>
                                <option value="1000">1000 categories per batch</option>
                            </select>
                        </div>
                        
                        <div class="button-group">
                            <button onclick="startMigration('categories')" class="btn btn-success" id="btn-start-migration-categories">
                                🚀 Start Migration
                            </button>
                            
                            <button onclick="checkMigrationStatus('categories')" class="btn" id="btn-check-status-categories">
                                📊 Check Status
                            </button>
                            
                            <button onclick="resetMigration('categories')" class="btn btn-danger" id="btn-reset-migration-categories">
                                🔄 Reset Migration
                            </button>
                            
                            <button onclick="downloadAllData('categories')" class="btn btn-warning">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Downloads Tab -->
                <div id="tab-downloads" class="tab-content">
                    <div class="preview-box">
                        <h3 class="section-title">📥 Downloaded Files</h3>
                        <div id="downloads-content">
                            <p style="text-align: center; color: #6c757d;">No downloads yet. Start a migration or use download buttons.</p>
                        </div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button onclick="refreshDownloads()" class="btn">
                                🔄 Refresh Downloads
                            </button>
                            <button onclick="clearDownloadsList()" class="btn btn-danger">
                                🗑️ Clear List
                            </button>
                        </div>
                    </div>
                    
                    <div class="file-downloads">
                        <h4 style="margin-top: 0; color: #495057;">Quick Download Options:</h4>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button onclick="downloadAllData('products')" class="btn btn-warning">
                                📦 Download All Products
                            </button>
                            <button onclick="downloadAllData('users')" class="btn btn-warning">
                                👥 Download All Users
                            </button>
                            <button onclick="downloadAllData('categories')" class="btn btn-warning">
                                📂 Download All Categories
                            </button>
                            <button onclick="downloadMigrationReport()" class="btn">
                                📊 Download Migration Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="migration-progress" style="display: none;">
                <h3 class="section-title">Migration Progress - <span id="current-type"></span></h3>
                <div style="background: #ddd; height: 20px; border-radius: 10px; margin: 15px 0; overflow: hidden;">
                    <div id="progress-bar" style="background: linear-gradient(90deg, #2271b1, #28a745); height: 100%; width: 0%; transition: width 0.5s ease;"></div>
                </div>
                <div id="progress-text" style="text-align: center; margin: 10px 0; font-size: 16px; font-weight: 600; color: #495057;"></div>
                <div id="batch-status" style="margin: 20px 0;"></div>
            </div>
            
            <div id="migration-log" class="migration-log">
                <h4 style="color: #6c757d; margin-top: 0;">Migration Log</h4>
                <div id="log-content"></div>
            </div>
        </div>

        <script>
            // Global variables
            let isMigrating = false;
            let downloadedFiles = [];
            
            // Tab switching
            function showTab(tabName) {
                // Hide all tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected tab
                document.getElementById(`tab-${tabName}`).classList.add('active');
                document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('active');
                
                // Load preview for the tab
                if (tabName !== 'downloads') {
                    loadMigrationPreview(tabName);
                } else {
                    refreshDownloads();
                }
            }
            
            // Save server settings
            document.getElementById('server-settings-form').onsubmit = function(e) {
                e.preventDefault();
                const serverUrl = document.getElementById('server-url').value;
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'mashry_save_server_settings',
                        'server_url': serverUrl
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const resultEl = document.getElementById('settings-result');
                    if (data.success) {
                        resultEl.innerHTML = '<span style="color:green; font-weight: 600;">✓ Settings saved successfully</span>';
                        setTimeout(() => {
                            resultEl.innerHTML = '';
                        }, 3000);
                    } else {
                        resultEl.innerHTML = '<span style="color:red; font-weight: 600;">✗ Error saving settings</span>';
                    }
                });
            };

            // Test server connection
            function testServerConnection() {
                const serverUrl = document.getElementById('server-url').value.trim();
                const testResultEl = document.getElementById('server-test-result');
                
                if (!serverUrl) {
                    testResultEl.innerHTML = '<div class="server-test-error">❌ Please enter a server URL</div>';
                    testResultEl.style.display = 'block';
                    return;
                }
                
                testResultEl.innerHTML = '<div style="color: #6c757d;">🔄 Testing connection to: ' + serverUrl + '...</div>';
                testResultEl.style.display = 'block';
                
                // Test server endpoint
                fetch(`<?php echo rest_url("mashry-connect/v1/test-server"); ?>?server_url=${encodeURIComponent(serverUrl)}`, {
                    headers: {
                        'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        testResultEl.innerHTML = '<div class="server-test-success">✅ Server connection successful!<br><small>' + data.message + '</small></div>';
                    } else {
                        testResultEl.innerHTML = '<div class="server-test-error">❌ Server connection failed: ' + (data.message || 'Unknown error') + '</div>';
                    }
                })
                .catch(error => {
                    testResultEl.innerHTML = '<div class="server-test-error">❌ Error testing connection: ' + error.message + '</div>';
                });
            }

            // Load migration preview
            function loadMigrationPreview(type) {
                const previewContent = document.getElementById(`preview-content-${type}`);
                previewContent.innerHTML = '<p style="text-align: center; color: #6c757d;">⏳ Loading preview...</p>';
                
                fetch(`<?php echo rest_url("mashry-connect/v1/export/"); ?>${type}?action=preview`, {
                    headers: {
                        'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    let html = `
                        <table class="stats-table">
                            <tr>
                                <td>Total ${type}:</td>
                                <td><strong style="font-size: 18px; color: #2271b1;">${data.total || 0}</strong></td>
                            </tr>
                            <tr>
                                <td>Already Migrated:</td>
                                <td><strong style="color: #28a745;">${data.migrated || 0}</strong></td>
                            </tr>
                            <tr>
                                <td>Pending:</td>
                                <td><strong style="color: #ffc107;">${data.pending || 0}</strong></td>
                            </tr>
                            <tr>
                                <td>Failed:</td>
                                <td><strong style="color: #dc3545;">${data.failed || 0}</strong></td>
                            </tr>
                            <tr>
                                <td>Progress:</td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width: ${data.progress_percentage || 0}%"></div>
                                        </div>
                                        <span style="font-weight: 600; color: #495057;">${data.progress_percentage || 0}%</span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    `;
                    
                    if (data.sample && data.sample.length > 0) {
                        html += `<h4 style="color: #495057; margin: 25px 0 15px 0;">📋 Sample Data (${data.sample.length} items)</h4>`;
                        
                        if (type === 'products') {
                            html += `
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product Name</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;
                            
                            data.sample.forEach(item => {
                                const statusColor = item.status === 'publish' ? '#28a745' : 
                                                   item.status === 'draft' ? '#6c757d' : 
                                                   item.status === 'private' ? '#007bff' : '#6c757d';
                                const statusClass = item.status === 'publish' ? 'status-publish' : 
                                                   item.status === 'draft' ? 'status-draft' : 
                                                   item.status === 'private' ? 'status-private' : '';
                                
                                html += `
                                    <tr>
                                        <td><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id || item.ID}</code></td>
                                        <td><strong>${item.name || item.post_title || 'N/A'}</strong></td>
                                        <td>
                                            <span class="status-badge ${statusClass}">
                                                ${item.status || item.post_status || 'N/A'}
                                            </span>
                                        </td>
                                        <td>
                                            ${item.edit_url ? 
                                                `<a href="${item.edit_url}" target="_blank" class="view-link">
                                                    👁️ View
                                                </a>` : 
                                                '-'
                                            }
                                        </td>
                                    </tr>
                                `;
                            });
                            
                            html += `</tbody></table>`;
                        } else if (type === 'users') {
                            html += `
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Display Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;
                            
                            data.sample.forEach(item => {
                                html += `
                                    <tr>
                                        <td><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id || item.ID}</code></td>
                                        <td><strong>${item.user_login || 'N/A'}</strong></td>
                                        <td>${item.user_email || 'N/A'}</td>
                                        <td>${item.display_name || 'N/A'}</td>
                                    </tr>
                                `;
                            });
                            
                            html += `</tbody></table>`;
                        } else if (type === 'categories') {
                            html += `
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Category Name</th>
                                            <th>Slug</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;
                            
                            data.sample.forEach(item => {
                                html += `
                                    <tr>
                                        <td><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id || item.term_id}</code></td>
                                        <td><strong>${item.name || 'N/A'}</strong></td>
                                        <td><code>${item.slug || 'N/A'}</code></td>
                                        <td>${item.description ? 
                                            `<span title="${item.description}">${item.description.substring(0, 50)}${item.description.length > 50 ? '...' : ''}</span>` : 
                                            '-'
                                        }</td>
                                    </tr>
                                `;
                            });
                            
                            html += `</tbody></table>`;
                        }
                    } else {
                        html += `<p style="text-align: center; color: #6c757d; padding: 20px;">No sample data available.</p>`;
                    }
                    
                    previewContent.innerHTML = html;
                })
                .catch(error => {
                    previewContent.innerHTML = `
                        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; text-align: center;">
                            <strong>❌ Error loading preview:</strong> ${error.message}
                        </div>
                    `;
                });
            }

            // Start migration
            function startMigration(type) {
                if (isMigrating) {
                    alert('⚠️ A migration is already in progress!');
                    return;
                }
                
                const batchSize = document.getElementById(`batch-size-${type}`).value;
                const forceRestart = confirm('Start migration from beginning?\n\nClick OK to restart from the beginning.\nClick Cancel to continue from where you left.');
                
                // Show progress section
                document.getElementById('migration-progress').style.display = 'block';
                document.getElementById('migration-log').style.display = 'block';
                document.getElementById('current-type').textContent = type.charAt(0).toUpperCase() + type.slice(1);
                
                // Clear log
                document.getElementById('log-content').innerHTML = '';
                
                // Start migration
                isMigrating = true;
                addToLog(`🚀 Starting ${type} migration...`, 'info');
                
                // Disable buttons
                document.querySelectorAll('.btn-success').forEach(btn => btn.disabled = true);
                
                fetch(`<?php echo rest_url("mashry-connect/v1/export/"); ?>${type}?action=start_migration&batch_size=${batchSize}&force_restart=${forceRestart}`, {
                    headers: {
                        'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addToLog(`✅ Migration started successfully`, 'success');
                        addToLog(`📊 Total items: ${data.total_products || data.total_users || data.total_categories || data.total} | Batch size: ${batchSize}`, 'info');
                        
                        // Start processing batches
                        processNextBatch(type, 1, batchSize);
                    } else {
                        addToLog(`❌ Failed to start migration: ${data.message || 'Unknown error'}`, 'error');
                        isMigrating = false;
                        document.querySelectorAll('.btn-success').forEach(btn => btn.disabled = false);
                    }
                })
                .catch(error => {
                    addToLog(`❌ Error starting migration: ${error.message}`, 'error');
                    isMigrating = false;
                    document.querySelectorAll('.btn-success').forEach(btn => btn.disabled = false);
                });
            }

            // Process next batch
            function processNextBatch(type, batchNumber, batchSize) {
                if (!isMigrating) return;
                
                addToLog(`🔄 Processing ${type} batch ${batchNumber}...`, 'info');
                updateProgressText(`Processing ${type} batch ${batchNumber}`);
                
                fetch(`<?php echo rest_url("mashry-connect/v1/export/"); ?>${type}?action=migrate_batch&batch=${batchNumber}&batch_size=${batchSize}`, {
                    headers: {
                        'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => response.json())
                .then(async data => {
                    if (data.success) {
                        const migratedCount = data[`${type}_migrated`] || 0;
                        const totalItems = data.total_items || 0;
                        const totalMigrated = data.total_migrated || 0;
                        
                        // Calculate progress percentage
                        const progressPercentage = totalItems > 0 ? Math.round((totalMigrated / totalItems) * 100) : 0;
                        updateProgressBar(progressPercentage);
                        
                        if (migratedCount > 0) {
                            addToLog(`✅ ${type.charAt(0).toUpperCase() + type.slice(1)} batch ${batchNumber}: ${migratedCount} items migrated (Total: ${totalMigrated}/${totalItems})`, 'success');
                            
                            // Send data to server AND download locally
                            const itemsData = data[`${type}_data`];
                            if (itemsData && itemsData.length > 0) {
                                // 1. Try to send to server
                                await sendBatchToServer(type, itemsData, batchNumber);
                                
                                // 2. Always download locally
                                downloadBatchLocally(type, itemsData, batchNumber);
                            }
                        } else {
                            addToLog(`⚠️ ${type.charAt(0).toUpperCase() + type.slice(1)} batch ${batchNumber}: No new items to migrate`, 'warning');
                        }
                        
                        // Check if there are more batches
                        if (data.has_more) {
                            // Wait 1 second before next batch
                            setTimeout(() => {
                                processNextBatch(type, batchNumber + 1, batchSize);
                            }, 1000);
                        } else {
                            migrationComplete(type);
                        }
                    } else {
                        addToLog(`❌ Batch ${batchNumber} failed: ${data.message || 'Unknown error'}`, 'error');
                        isMigrating = false;
                        document.querySelectorAll('.btn-success').forEach(btn => btn.disabled = false);
                    }
                })
                .catch(error => {
                    addToLog(`❌ Error processing batch ${batchNumber}: ${error.message}`, 'error');
                    isMigrating = false;
                    document.querySelectorAll('.btn-success').forEach(btn => btn.disabled = false);
                });
            }

            // Send batch to server - REAL SERVER SEND
            async function sendBatchToServer(type, itemsData, batchNumber) {
                const serverUrl = document.getElementById('server-url').value.trim();
                
                if (!serverUrl) {
                    addToLog(`⚠️ Server URL is empty. Data will be downloaded locally only.`, 'warning');
                    return;
                }
                
                addToLog(`📤 Sending ${type} batch ${batchNumber} to server at ${serverUrl}...`, 'info');
                
                try {
                    const response = await fetch(`${serverUrl}/import/${type}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-Key': 'mashry-secret-static-key-here-123'
                        },
                        body: JSON.stringify(itemsData)
                    });
                    
                    if (!response.ok) {
                        let errorMessage = `Server error ${response.status}: ${response.statusText}`;
                        try {
                            const errorData = await response.json();
                            errorMessage = errorData.message || errorMessage;
                        } catch (e) {
                            const errorText = await response.text();
                            if (errorText) {
                                errorMessage += ` - ${errorText.substring(0, 100)}`;
                            }
                        }
                        throw new Error(errorMessage);
                    }
                    
                    const result = await response.json();
                    addToLog(`✅ ${type.charAt(0).toUpperCase() + type.slice(1)} batch ${batchNumber} sent to server successfully: ${result.message || 'Received by server'}`, 'success');
                    
                } catch (error) {
                    console.error('Error sending to server:', error);
                    
                    if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                        addToLog(`❌ Cannot connect to server at ${serverUrl}. Make sure Node.js server is running.`, 'error');
                    } else if (error.message.includes('CORS')) {
                        addToLog(`❌ CORS error: Server needs to allow requests from WordPress domain.`, 'error');
                    } else {
                        addToLog(`❌ Error sending to server: ${error.message}`, 'error');
                    }
                    
                    // Add server setup instructions
                    addToLog(`💡 <strong>To set up Node.js server:</strong><br>
                    1. Install Node.js<br>
                    2. Create server.js file with endpoints:<br>
                    <code style="background: #f8f9fa; padding: 2px 5px; border-radius: 3px;">POST /import/products</code><br>
                    <code style="background: #f8f9fa; padding: 2px 5px; border-radius: 3px;">POST /import/users</code><br>
                    <code style="background: #f8f9fa; padding: 2px 5px; border-radius: 3px;">POST /import/categories</code>`, 'info');
                }
            }

            // Download batch locally as JSON file
            function downloadBatchLocally(type, itemsData, batchNumber) {
                const dataStr = JSON.stringify(itemsData, null, 2);
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                const fileName = `${type}_batch_${batchNumber}_${timestamp}.json`;
                const fileSize = (dataStr.length / 1024).toFixed(2);
                
                // Create download link
                const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
                const linkElement = document.createElement('a');
                linkElement.setAttribute('href', dataUri);
                linkElement.setAttribute('download', fileName);
                linkElement.setAttribute('id', `download-${type}-${batchNumber}`);
                linkElement.style.display = 'none';
                document.body.appendChild(linkElement);
                
                // Store in downloads list
                downloadedFiles.unshift({
                    type: type,
                    batch: batchNumber,
                    fileName: fileName,
                    fileSize: fileSize + ' KB',
                    timestamp: new Date().toLocaleString(),
                    downloadLink: linkElement
                });
                
                // Update downloads tab
                refreshDownloads();
                
                // Trigger download automatically
                linkElement.click();
                
                addToLog(`📥 Batch ${batchNumber} downloaded locally: ${fileName} (${fileSize} KB)`, 'success');
            }

            // Download all data of a type
            function downloadAllData(type) {
                addToLog(`📥 Downloading all ${type}...`, 'info');
                
                fetch(`<?php echo rest_url("mashry-connect/v1/export/"); ?>${type}?action=get_all`, {
                    headers: {
                        'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const dataStr = JSON.stringify(data, null, 2);
                    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                    const fileName = `${type}_all_${timestamp}.json`;
                    const fileSize = (dataStr.length / 1024).toFixed(2);
                    
                    // Create download link
                    const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
                    const linkElement = document.createElement('a');
                    linkElement.setAttribute('href', dataUri);
                    linkElement.setAttribute('download', fileName);
                    document.body.appendChild(linkElement);
                    linkElement.click();
                    document.body.removeChild(linkElement);
                    
                    // Store in downloads list
                    downloadedFiles.unshift({
                        type: type,
                        batch: 'all',
                        fileName: fileName,
                        fileSize: fileSize + ' KB',
                        timestamp: new Date().toLocaleString(),
                        isAllData: true
                    });
                    
                    // Update downloads tab
                    refreshDownloads();
                    
                    addToLog(`✅ All ${type} downloaded: ${fileName} (${fileSize} KB)`, 'success');
                    alert(`✅ All ${type} downloaded successfully! File: ${fileName}`);
                })
                .catch(error => {
                    addToLog(`❌ Error downloading all ${type}: ${error.message}`, 'error');
                    alert(`❌ Error downloading all ${type}: ${error.message}`);
                });
            }

            // Refresh downloads list
            function refreshDownloads() {
                const downloadsContent = document.getElementById('downloads-content');
                
                if (downloadedFiles.length === 0) {
                    downloadsContent.innerHTML = '<p style="text-align: center; color: #6c757d;">No downloads yet. Start a migration or use download buttons.</p>';
                    return;
                }
                
                let html = `
                    <div style="margin-bottom: 15px;">
                        <strong>Total files:</strong> ${downloadedFiles.length}
                    </div>
                `;
                
                downloadedFiles.forEach((file, index) => {
                    const icon = file.type === 'products' ? '📦' : 
                                file.type === 'users' ? '👥' : '📂';
                    const batchLabel = file.batch === 'all' ? 'All Data' : `Batch ${file.batch}`;
                    
                    html += `
                        <div class="download-item">
                            <div>
                                <div class="download-filename">
                                    ${icon} ${file.fileName}
                                </div>
                                <div style="font-size: 12px; color: #6c757d;">
                                    ${file.type} • ${batchLabel} • ${file.timestamp}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="download-size">${file.fileSize}</div>
                                <a href="#" onclick="event.preventDefault(); downloadFileAgain(${index});" class="download-link">
                                    ⬇️ Download Again
                                </a>
                            </div>
                        </div>
                    `;
                });
                
                downloadsContent.innerHTML = html;
            }

            // Download file again from list
            function downloadFileAgain(index) {
                const file = downloadedFiles[index];
                
                if (file.downloadLink) {
                    file.downloadLink.click();
                } else {
                    // Recreate download for "all data" files
                    alert(`To download "${file.fileName}" again, please use the "Download All" button for ${file.type}.`);
                }
            }

            // Clear downloads list
            function clearDownloadsList() {
                if (confirm('Clear all downloads from the list?')) {
                    downloadedFiles = [];
                    refreshDownloads();
                    addToLog('🗑️ Downloads list cleared', 'info');
                }
            }

            // Download migration report
            function downloadMigrationReport() {
                const report = {
                    generated_at: new Date().toISOString(),
                    server_url: document.getElementById('server-url').value,
                    downloaded_files: downloadedFiles,
                    summary: {
                        total_files: downloadedFiles.length,
                        products_files: downloadedFiles.filter(f => f.type === 'products').length,
                        users_files: downloadedFiles.filter(f => f.type === 'users').length,
                        categories_files: downloadedFiles.filter(f => f.type === 'categories').length
                    }
                };
                
                const dataStr = JSON.stringify(report, null, 2);
                const fileName = `migration_report_${new Date().getTime()}.json`;
                const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
                
                const linkElement = document.createElement('a');
                linkElement.setAttribute('href', dataUri);
                linkElement.setAttribute('download', fileName);
                document.body.appendChild(linkElement);
                linkElement.click();
                document.body.removeChild(linkElement);
                
                addToLog(`📊 Migration report downloaded: ${fileName}`, 'success');
            }

            // Migration complete
            function migrationComplete(type) {
                isMigrating = false;
                addToLog(`🎉 ${type.charAt(0).toUpperCase() + type.slice(1)} migration completed successfully!`, 'success');
                updateProgressBar(100);
                updateProgressText(`${type.charAt(0).toUpperCase() + type.slice(1)} migration completed! 🎉`);
                
                // Enable buttons
                document.querySelectorAll('.btn-success').forEach(btn => btn.disabled = false);
                
                // Final status check
                checkMigrationStatus(type);
            }

            // Check migration status
            function checkMigrationStatus(type) {
                fetch(`<?php echo rest_url("mashry-connect/v1/export/"); ?>${type}?action=migration_status`, {
                    headers: {
                        'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const statusDiv = document.getElementById('batch-status');
                    statusDiv.innerHTML = `
                        <div class="preview-box">
                            <h4 style="color: #495057; margin-top: 0;">📈 Current Status</h4>
                            <table class="stats-table">
                                <tr>
                                    <td>Total:</td>
                                    <td><strong>${data.stats.total}</strong></td>
                                </tr>
                                <tr>
                                    <td>Migrated:</td>
                                    <td><strong style="color: #28a745;">${data.stats.migrated}</strong></td>
                                </tr>
                                <tr>
                                    <td>Failed:</td>
                                    <td><strong style="color: #dc3545;">${data.stats.failed}</strong></td>
                                </tr>
                                <tr>
                                    <td>Progress:</td>
                                    <td><strong>${data.progress}%</strong></td>
                                </tr>
                                <tr>
                                    <td>Status:</td>
                                    <td><strong style="color: ${data.is_completed ? '#28a745' : '#ffc107'};">${data.is_completed ? '✅ Completed' : '⏳ In Progress'}</strong></td>
                                </tr>
                            </table>
                        </div>
                    `;
                    
                    // Update the preview
                    loadMigrationPreview(type);
                });
            }

            // Reset migration
            function resetMigration(type) {
                if (!confirm(`⚠️ Are you sure you want to reset ${type} migration?\n\nThis will delete all tracking data and you will need to start over.`)) {
                    return;
                }
                
                fetch(`<?php echo rest_url("mashry-connect/v1/export/"); ?>${type}?action=reset_migration`, {
                    headers: {
                        'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`✅ ${type.charAt(0).toUpperCase() + type.slice(1)} migration reset successfully!`);
                        loadMigrationPreview(type);
                        
                        // Clear progress
                        document.getElementById('migration-progress').style.display = 'none';
                        document.getElementById('batch-status').innerHTML = '';
                        updateProgressBar(0);
                        updateProgressText('');
                        document.getElementById('log-content').innerHTML = '';
                    } else {
                        alert(`❌ Error resetting migration: ${data.message || 'Unknown error'}`);
                    }
                })
                .catch(error => {
                    alert(`❌ Error resetting migration: ${error.message}`);
                });
            }

            function updateProgressBar(percentage) {
                const progressBar = document.getElementById('progress-bar');
                progressBar.style.width = percentage + '%';
            }

            function updateProgressText(text) {
                document.getElementById('progress-text').innerHTML = text;
            }

            function addToLog(message, type = 'info') {
                const logContent = document.getElementById('log-content');
                const timestamp = new Date().toLocaleTimeString();
                
                let typeClass = 'log-info';
                if (type === 'error') typeClass = 'log-error';
                else if (type === 'success') typeClass = 'log-success';
                else if (type === 'warning') typeClass = 'log-warning';
                
                logContent.innerHTML += `
                    <div class="log-entry">
                        <div class="log-time">[${timestamp}]</div>
                        <div class="log-message ${typeClass}">${message}</div>
                    </div>
                `;
                
                // Auto-scroll to bottom
                logContent.scrollTop = logContent.scrollHeight;
            }

            // Load preview on page load
            document.addEventListener('DOMContentLoaded', function() {
                loadMigrationPreview('products');
            });
        </script>
    </div>
    <?php
}

// Test server connection
function mashry_connect_test_server(WP_REST_Request $request) {
    $server_url = $request->get_param('server_url');
    
    if (empty($server_url)) {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Server URL is required'
        ]);
    }
    
    // Test server connection
    $response = wp_remote_get($server_url, [
        'timeout' => 10,
        'headers' => [
            'X-API-Key' => 'mashry-secret-static-key-here-123'
        ]
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
            'message' => 'Server is responding correctly (Status: 200)'
        ]);
    } else {
        return rest_ensure_response([
            'success' => false,
            'message' => "Server responded with status: {$status_code}. Make sure your Node.js server is running and the endpoint exists."
        ]);
    }
}

// AJAX handler for saving server settings
add_action('wp_ajax_mashry_save_server_settings', 'mashry_connect_save_server_settings');

function mashry_connect_save_server_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $server_url = sanitize_text_field($_POST['server_url']);
    update_option('mashry_node_server_url', $server_url);
    
    wp_send_json_success(array('message' => 'Server settings saved'));
}

function mashry_connect_settings_link($actions, $plugin_file)
{
    // Get the base name of your plugin file
    $plugin_base = plugin_basename(__FILE__);

    // Check if the current plugin is yours
    if ($plugin_base === $plugin_file) {
        // Create the URL for the settings page
        $settings_url = add_query_arg(
            "page",
            "mashry-connect-settings",
            admin_url("options-general.php"),
        );

        // Create the HTML for the link
        $settings_link =
            '<a href="' .
            esc_url($settings_url) .
            '">' .
            __("Settings", "mashry-connect") .
            "</a>";

        // Add the link to the beginning of the actions array
        array_unshift($actions, $settings_link);
    }

    return $actions;
}

/**
 * Checks for a valid static API key in the Authorization header.
 */
function mashry_connect_check_api_key(WP_REST_Request $request)
{
    $auth_header = $request->get_header("Authorization");

    // Check if the Authorization header exists and starts with "Bearer "
    if ($auth_header && strpos($auth_header, "Bearer ") === 0) {
        $key = substr($auth_header, 7);
        if ($key === MASHRY_CONNECT_API_KEY) {
            return true;
        }
    }

    // If the check fails, return a forbidden error
    return new WP_Error(
        "rest_forbidden",
        __("Invalid or missing Authorization header.", "mashry-connect"),
        ["status" => 401],
    );
}

add_action("admin_menu", "mashry_connect_add_settings_page");
add_filter("plugin_action_links", "mashry_connect_settings_link", 10, 2);
add_action("rest_api_init", "mashry_connect_register_api_route");