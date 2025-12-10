<?php
// Admin pages for mashry Connect

if (!defined('ABSPATH')) {
    exit;
}

function mashry_connect_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $server_url = get_option('mashry_node_server_url', 'http://localhost:5000');
    ?>
    
    <div class="wrap">
        <h1>mashry Connect</h1>
        
        <div class="mashry-settings-form">
            <h2>Server Settings</h2>
            <form id="server-settings-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="server-url">Server URL:</label>
                        </th>
                        <td>
                            <input type="url" id="server-url" name="server_url" 
                                   value="<?php echo esc_attr($server_url); ?>" 
                                   class="regular-text"
                                   placeholder="http://localhost:5000">
                            <p class="description">Enter the URL of your Node.js server</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        Save Settings
                    </button>
                    <button type="button" id="test-connection-btn" class="button">
                        Test Connection
                    </button>
                    <span id="settings-result" style="margin-left: 10px;"></span>
                </p>
                
                <div id="server-test-result"></div>
            </form>
        </div>
        
        <div class="mashry-migration-container">
            <h2>Migration with Batch Processing</h2>
            
            <div class="mashry-tabs">
                <div class="mashry-tab-buttons">
                    <button class="mashry-tab-button active" data-tab="products">
                        Products
                    </button>
                    <button class="mashry-tab-button" data-tab="users">
                        Users
                    </button>
                    <button class="mashry-tab-button" data-tab="categories">
                        Categories
                    </button>
                    <button class="mashry-tab-button" data-tab="downloads">
                        Downloads
                    </button>
                </div>
                
                <!-- Products Tab -->
                <div id="tab-products" class="mashry-tab-content active">
                    <div class="mashry-preview-box">
                        <h3>Products Migration Preview</h3>
                        <div id="preview-content-products">
                            <p>Loading preview...</p>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button class="button refresh-preview-btn" data-type="products">
                                Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group">
                        <div>
                            <label>Batch Size:</label>
                            <select id="batch-size-products" class="mashry-select">
                                <option value="100">100 per batch</option>
                                <option value="500" selected>500 per batch</option>
                                <option value="1000">1000 per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group">
                            <button class="button button-primary start-migration-btn" data-type="products">
                                Start Migration
                            </button>
                            <button class="button check-status-btn" data-type="products">
                                Check Status
                            </button>
                            <button class="button button-secondary reset-migration-btn" data-type="products">
                                Reset Migration
                            </button>
                            <button class="button download-all-btn" data-type="products">
                                Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Users Tab -->
                <div id="tab-users" class="mashry-tab-content">
                    <div class="mashry-preview-box">
                        <h3>Users Migration Preview</h3>
                        <div id="preview-content-users">
                            <p>Loading preview...</p>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button class="button refresh-preview-btn" data-type="users">
                                Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group">
                        <div>
                            <label>Batch Size:</label>
                            <select id="batch-size-users" class="mashry-select">
                                <option value="100">100 per batch</option>
                                <option value="500" selected>500 per batch</option>
                                <option value="1000">1000 per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group">
                            <button class="button button-primary start-migration-btn" data-type="users">
                                Start Migration
                            </button>
                            <button class="button check-status-btn" data-type="users">
                                Check Status
                            </button>
                            <button class="button button-secondary reset-migration-btn" data-type="users">
                                Reset Migration
                            </button>
                            <button class="button download-all-btn" data-type="users">
                                Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Categories Tab -->
                <div id="tab-categories" class="mashry-tab-content">
                    <div class="mashry-preview-box">
                        <h3>Categories Migration Preview</h3>
                        <div id="preview-content-categories">
                            <p>Loading preview...</p>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button class="button refresh-preview-btn" data-type="categories">
                                Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group">
                        <div>
                            <label>Batch Size:</label>
                            <select id="batch-size-categories" class="mashry-select">
                                <option value="100">100 per batch</option>
                                <option value="500" selected>500 per batch</option>
                                <option value="1000">1000 per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group">
                            <button class="button button-primary start-migration-btn" data-type="categories">
                                Start Migration
                            </button>
                            <button class="button check-status-btn" data-type="categories">
                                Check Status
                            </button>
                            <button class="button button-secondary reset-migration-btn" data-type="categories">
                                Reset Migration
                            </button>
                            <button class="button download-all-btn" data-type="categories">
                                Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Downloads Tab -->
                <div id="tab-downloads" class="mashry-tab-content">
                    <div class="mashry-preview-box">
                        <h3>Downloaded Files</h3>
                        <div id="downloads-content">
                            <p>No downloads yet.</p>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button id="refresh-downloads" class="button">
                                Refresh Downloads
                            </button>
                            <button id="clear-downloads" class="button button-secondary">
                                Clear List
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-quick-downloads">
                        <h4>Quick Downloads:</h4>
                        <div class="mashry-button-group">
                            <button class="button download-all-btn" data-type="products">
                                Download All Products
                            </button>
                            <button class="button download-all-btn" data-type="users">
                                Download All Users
                            </button>
                            <button class="button download-all-btn" data-type="categories">
                                Download All Categories
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="migration-progress" style="display: none; margin-top: 30px;">
                <h3>Migration Progress - <span id="current-type"></span></h3>
                <div style="background: #ddd; height: 20px; border-radius: 10px; margin: 15px 0; overflow: hidden;">
                    <div id="progress-bar" style="background: #2271b1; height: 100%; width: 0%;"></div>
                </div>
                <div id="progress-text" style="text-align: center;"></div>
                <div id="batch-status"></div>
            </div>
            
            <div id="migration-log" style="display: none; margin-top: 30px; max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 15px; border: 1px solid #ddd;">
                <h4>Migration Log</h4>
                <div id="log-content"></div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Tab switching
        $('.mashry-tab-button').on('click', function() {
            $('.mashry-tab-button').removeClass('active');
            $('.mashry-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#tab-' + $(this).data('tab')).addClass('active');
        });
        
        // Load initial preview
        loadMigrationPreview('products');
        
        // Refresh preview
        $('.refresh-preview-btn').on('click', function() {
            var type = $(this).data('type');
            loadMigrationPreview(type);
        });
        
        // Load preview function
        function loadMigrationPreview(type) {
            $('#preview-content-' + type).html('<p>Loading...</p>');
            
            $.ajax({
                url: mashryConnect.rest_url + 'export/' + type,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + mashryConnect.api_key
                },
                data: {
                    action: 'preview'
                },
                success: function(response) {
                    var html = '<p>Total: ' + response.total + '</p>' +
                              '<p>Migrated: ' + response.migrated + '</p>' +
                              '<p>Pending: ' + response.pending + '</p>';
                    $('#preview-content-' + type).html(html);
                },
                error: function() {
                    $('#preview-content-' + type).html('<p>Error loading preview</p>');
                }
            });
        }
        
        // Start migration
        $('.start-migration-btn').on('click', function() {
            var type = $(this).data('type');
            alert('Migration started for ' + type);
        });
        
        // Check status
        $('.check-status-btn').on('click', function() {
            var type = $(this).data('type');
            alert('Checking status for ' + type);
        });
        
        // Reset migration
        $('.reset-migration-btn').on('click', function() {
            var type = $(this).data('type');
            if (confirm('Reset ' + type + ' migration?')) {
                alert('Migration reset for ' + type);
            }
        });
        
        // Download all
        $('.download-all-btn').on('click', function() {
            var type = $(this).data('type');
            alert('Downloading all ' + type);
        });
        
        // Save settings
        $('#server-settings-form').on('submit', function(e) {
            e.preventDefault();
            var serverUrl = $('#server-url').val();
            
            $.ajax({
                url: mashryConnect.ajax_url,
                method: 'POST',
                data: {
                    action: 'mashry_save_server_settings',
                    server_url: serverUrl,
                    nonce: mashryConnect.nonce
                },
                success: function() {
                    $('#settings-result').html('<span style="color:green">Settings saved!</span>');
                },
                error: function() {
                    $('#settings-result').html('<span style="color:red">Error saving settings</span>');
                }
            });
        });
        
        // Test connection
        $('#test-connection-btn').on('click', function() {
            var serverUrl = $('#server-url').val();
            $('#server-test-result').html('<p>Testing connection...</p>');
            
            $.ajax({
                url: mashryConnect.rest_url + 'test-server',
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + mashryConnect.api_key
                },
                data: {
                    server_url: serverUrl
                },
                success: function(response) {
                    if (response.success) {
                        $('#server-test-result').html('<p style="color:green">' + response.message + '</p>');
                    } else {
                        $('#server-test-result').html('<p style="color:red">' + response.message + '</p>');
                    }
                },
                error: function() {
                    $('#server-test-result').html('<p style="color:red">Connection test failed</p>');
                }
            });
        });
    });
    </script>
    <?php
}