<?php
/**
 * Admin settings page for mashry Connect
 */

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Renders the content for the settings page.
 */
function mashry_connect_render_settings_page() {
    if (!current_user_can("manage_options")) {
        return;
    }

    // Get saved Node.js server URL
    $node_server_url = get_option('mashry_node_server_url', 'http://localhost:5000');
    ?>

    <div class="wrap">
        <h1>mashry Connect</h1>
        
        <div class="mashry-settings-form">
            <h3 style="margin-top: 0;">Server Settings</h3>
            <form id="server-settings-form">
                <label for="server-url" class="mashry-control-label">Server URL:</label>
                <input type="url" id="server-url" name="server_url" 
                       value="<?php echo esc_attr($node_server_url); ?>" 
                       placeholder="http://localhost:5000">
                
                <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
                    <button type="submit" class="mashry-btn">
                        💾 Save Settings
                    </button>
                    <button type="button" id="test-connection-btn" class="mashry-btn mashry-btn-secondary">
                        🧪 Test Connection
                    </button>
                    <span id="settings-result" style="margin-left: 10px;"></span>
                </div>
                
                <div id="server-test-result" class="mashry-server-test-result"></div>
            </form>
        </div>

        <div class="mashry-migration-container">
            <h2 style="color: #2271b1; margin-top: 0;">📦 Migration with Batch Processing</h2>
            
            <div class="mashry-tab-container">
                <div class="mashry-tab-buttons">
                    <button class="mashry-tab-button active" data-tab="products">
                        📦 Products
                    </button>
                    <button class="mashry-tab-button" data-tab="users">
                        👥 Users
                    </button>
                    <button class="mashry-tab-button" data-tab="categories">
                        📂 Categories
                    </button>
                    <button class="mashry-tab-button" data-tab="downloads">
                        📥 Downloads
                    </button>
                </div>
                
                <!-- Products Tab -->
                <div id="tab-products" class="mashry-tab-content active">
                    <div class="mashry-preview-box">
                        <h3 class="mashry-section-title">Products Migration Preview</h3>
                        <div id="preview-content-products"></div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="mashry-btn refresh-preview-btn" data-type="products">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group">
                        <div>
                            <label class="mashry-control-label">Batch Size:</label>
                            <select id="batch-size-products" class="mashry-select">
                                <option value="100">100 products per batch</option>
                                <option value="500" selected>500 products per batch</option>
                                <option value="1000">1000 products per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group">
                            <button class="mashry-btn mashry-btn-success start-migration-btn" data-type="products" id="btn-start-migration-products">
                                🚀 Start Migration
                            </button>
                            
                            <button class="mashry-btn check-status-btn" data-type="products" id="btn-check-status-products">
                                📊 Check Status
                            </button>
                            
                            <button class="mashry-btn mashry-btn-danger reset-migration-btn" data-type="products" id="btn-reset-migration-products">
                                🔄 Reset Migration
                            </button>
                            
                            <button class="mashry-btn mashry-btn-warning download-all-btn" data-type="products">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Users Tab -->
                <div id="tab-users" class="mashry-tab-content">
                    <div class="mashry-preview-box">
                        <h3 class="mashry-section-title">Users Migration Preview</h3>
                        <div id="preview-content-users"></div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="mashry-btn refresh-preview-btn" data-type="users">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group">
                        <div>
                            <label class="mashry-control-label">Batch Size:</label>
                            <select id="batch-size-users" class="mashry-select">
                                <option value="100">100 users per batch</option>
                                <option value="500" selected>500 users per batch</option>
                                <option value="1000">1000 users per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group">
                            <button class="mashry-btn mashry-btn-success start-migration-btn" data-type="users" id="btn-start-migration-users">
                                🚀 Start Migration
                            </button>
                            
                            <button class="mashry-btn check-status-btn" data-type="users" id="btn-check-status-users">
                                📊 Check Status
                            </button>
                            
                            <button class="mashry-btn mashry-btn-danger reset-migration-btn" data-type="users" id="btn-reset-migration-users">
                                🔄 Reset Migration
                            </button>
                            
                            <button class="mashry-btn mashry-btn-warning download-all-btn" data-type="users">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Categories Tab -->
                <div id="tab-categories" class="mashry-tab-content">
                    <div class="mashry-preview-box">
                        <h3 class="mashry-section-title">Categories Migration Preview</h3>
                        <div id="preview-content-categories"></div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="mashry-btn refresh-preview-btn" data-type="categories">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group">
                        <div>
                            <label class="mashry-control-label">Batch Size:</label>
                            <select id="batch-size-categories" class="mashry-select">
                                <option value="100">100 categories per batch</option>
                                <option value="500" selected>500 categories per batch</option>
                                <option value="1000">1000 categories per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group">
                            <button class="mashry-btn mashry-btn-success start-migration-btn" data-type="categories" id="btn-start-migration-categories">
                                🚀 Start Migration
                            </button>
                            
                            <button class="mashry-btn check-status-btn" data-type="categories" id="btn-check-status-categories">
                                📊 Check Status
                            </button>
                            
                            <button class="mashry-btn mashry-btn-danger reset-migration-btn" data-type="categories" id="btn-reset-migration-categories">
                                🔄 Reset Migration
                            </button>
                            
                            <button class="mashry-btn mashry-btn-warning download-all-btn" data-type="categories">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Downloads Tab -->
                <div id="tab-downloads" class="mashry-tab-content">
                    <div class="mashry-preview-box">
                        <h3 class="mashry-section-title">📥 Downloaded Files</h3>
                        <div id="downloads-content">
                            <p style="text-align: center; color: #6c757d;">No downloads yet. Start a migration or use download buttons.</p>
                        </div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button id="refresh-downloads" class="mashry-btn">
                                🔄 Refresh Downloads
                            </button>
                            <button id="clear-downloads" class="mashry-btn mashry-btn-danger">
                                🗑️ Clear List
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-file-downloads">
                        <h4 style="margin-top: 0; color: #495057;">Quick Download Options:</h4>
                        <div class="mashry-button-group">
                            <button class="mashry-btn mashry-btn-warning download-all-btn" data-type="products">
                                📦 Download All Products
                            </button>
                            <button class="mashry-btn mashry-btn-warning download-all-btn" data-type="users">
                                👥 Download All Users
                            </button>
                            <button class="mashry-btn mashry-btn-warning download-all-btn" data-type="categories">
                                📂 Download All Categories
                            </button>
                            <button id="download-report" class="mashry-btn">
                                📊 Download Migration Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="migration-progress" style="display: none;">
                <h3 class="mashry-section-title">Migration Progress - <span id="current-type"></span></h3>
                <div class="mashry-progress-bar-bg" style="height: 20px; margin: 15px 0;">
                    <div id="progress-bar" class="mashry-progress-bar-fill" style="width: 0%;"></div>
                </div>
                <div id="progress-text" style="text-align: center; margin: 10px 0; font-size: 16px; font-weight: 600; color: #495057;"></div>
                <div id="batch-status" style="margin: 20px 0;"></div>
            </div>
            
            <div id="migration-log" class="mashry-migration-log">
                <h4 style="color: #6c757d; margin-top: 0;">Migration Log</h4>
                <div id="log-content"></div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler for saving server settings
 */
function mashry_connect_save_server_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    check_ajax_referer('mashry-connect-nonce', 'nonce');
    
    $server_url = sanitize_text_field($_POST['server_url']);
    update_option('mashry_node_server_url', $server_url);
    
    wp_send_json_success(array('message' => 'Server settings saved'));
}
add_action('wp_ajax_mashry_save_server_settings', 'mashry_connect_save_server_settings');