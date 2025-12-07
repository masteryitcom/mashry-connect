<?php
// admin-pages.php

function mashry_connect_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $server_url = get_option('mashry_node_server_url', 'http://localhost:5000');
    ?>
    
    <div class="wrap">
        <h1>mashry Connect</h1>
        
        <div class="mashry-settings-form" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 30px; max-width: 600px; border-radius: 5px;">
            <h3 style="margin-top: 0;">Server Settings</h3>
            <form id="server-settings-form">
                <label style="font-weight: 600; margin-bottom: 8px; display: block; color: #495057;">Server URL:</label>
                <input type="url" id="server-url" name="server_url" 
                       value="<?php echo esc_attr($server_url); ?>" 
                       placeholder="http://localhost:5000" 
                       style="width: 100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px;">
                
                <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
                    <button type="submit" class="button button-primary">
                        💾 Save Settings
                    </button>
                    <button type="button" id="test-connection-btn" class="button">
                        🧪 Test Connection
                    </button>
                    <span id="settings-result" style="margin-left: 10px;"></span>
                </div>
                
                <div id="server-test-result" style="margin-top: 15px; padding: 12px; border-radius: 5px; display: none;"></div>
            </form>
        </div>

        <div class="mashry-migration-container" style="margin-top: 40px; padding: 25px; background: #fff; border-radius: 5px; border: 1px solid #ccd0d4; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="color: #2271b1; margin-top: 0;">📦 Migration with Batch Processing</h2>
            
            <div class="mashry-tab-container" style="margin: 20px 0;">
                <div class="mashry-tab-buttons" style="display: flex; border-bottom: 2px solid #dee2e6; margin-bottom: -1px;">
                    <button class="mashry-tab-button active" data-tab="products" style="padding: 12px 24px; background: #f8f9fa; border: none; border-bottom: 3px solid #2271b1; cursor: pointer; font-size: 14px; font-weight: 600; color: #2271b1;">
                        📦 Products
                    </button>
                    <button class="mashry-tab-button" data-tab="users" style="padding: 12px 24px; background: #f8f9fa; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-size: 14px; font-weight: 500; color: #495057;">
                        👥 Users
                    </button>
                    <button class="mashry-tab-button" data-tab="categories" style="padding: 12px 24px; background: #f8f9fa; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-size: 14px; font-weight: 500; color: #495057;">
                        📂 Categories
                    </button>
                </div>
                
                <!-- Products Tab -->
                <div id="tab-products" class="mashry-tab-content" style="padding: 25px; background: white; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px; display: block;">
                    <div class="mashry-preview-box" style="background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 5px; border: 1px solid #dee2e6;">
                        <h3 style="color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; margin-bottom: 20px;">Products Migration Preview</h3>
                        <div id="preview-content-products">
                            <p style="text-align: center; color: #6c757d;">⏳ Loading preview...</p>
                        </div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="button refresh-preview-btn" data-type="products">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group" style="display: flex; align-items: center; gap: 20px; margin: 25px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">
                        <div>
                            <label style="font-weight: 600; margin-bottom: 8px; display: block; color: #495057;">Batch Size:</label>
                            <select id="batch-size-products" style="padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; background: white; font-size: 14px; min-width: 200px;">
                                <option value="100">100 products per batch</option>
                                <option value="500" selected>500 products per batch</option>
                                <option value="1000">1000 products per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="button button-primary start-migration-btn" data-type="products" id="btn-start-migration-products">
                                🚀 Start Migration
                            </button>
                            
                            <button class="button check-status-btn" data-type="products" id="btn-check-status-products">
                                📊 Check Status
                            </button>
                            
                            <button class="button button-secondary reset-migration-btn" data-type="products" id="btn-reset-migration-products">
                                🔄 Reset Migration
                            </button>
                            
                            <button class="button download-all-btn" data-type="products" style="background: #ffc107; color: #212529; border: none; border-radius: 4px; cursor: pointer; padding: 10px 20px; font-size: 14px; font-weight: 500;">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Users Tab -->
                <div id="tab-users" class="mashry-tab-content" style="padding: 25px; background: white; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px; display: none;">
                    <div class="mashry-preview-box" style="background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 5px; border: 1px solid #dee2e6;">
                        <h3 style="color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; margin-bottom: 20px;">Users Migration Preview</h3>
                        <div id="preview-content-users">
                            <p style="text-align: center; color: #6c757d;">⏳ Loading preview...</p>
                        </div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="button refresh-preview-btn" data-type="users">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group" style="display: flex; align-items: center; gap: 20px; margin: 25px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">
                        <div>
                            <label style="font-weight: 600; margin-bottom: 8px; display: block; color: #495057;">Batch Size:</label>
                            <select id="batch-size-users" style="padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; background: white; font-size: 14px; min-width: 200px;">
                                <option value="100">100 users per batch</option>
                                <option value="500" selected>500 users per batch</option>
                                <option value="1000">1000 users per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="button button-primary start-migration-btn" data-type="users" id="btn-start-migration-users">
                                🚀 Start Migration
                            </button>
                            
                            <button class="button check-status-btn" data-type="users" id="btn-check-status-users">
                                📊 Check Status
                            </button>
                            
                            <button class="button button-secondary reset-migration-btn" data-type="users" id="btn-reset-migration-users">
                                🔄 Reset Migration
                            </button>
                            
                            <button class="button download-all-btn" data-type="users" style="background: #ffc107; color: #212529; border: none; border-radius: 4px; cursor: pointer; padding: 10px 20px; font-size: 14px; font-weight: 500;">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Categories Tab -->
                <div id="tab-categories" class="mashry-tab-content" style="padding: 25px; background: white; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px; display: none;">
                    <div class="mashry-preview-box" style="background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 5px; border: 1px solid #dee2e6;">
                        <h3 style="color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; margin-bottom: 20px;">Categories Migration Preview</h3>
                        <div id="preview-content-categories">
                            <p style="text-align: center; color: #6c757d;">⏳ Loading preview...</p>
                        </div>
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="button refresh-preview-btn" data-type="categories">
                                🔄 Refresh Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="mashry-control-group" style="display: flex; align-items: center; gap: 20px; margin: 25px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">
                        <div>
                            <label style="font-weight: 600; margin-bottom: 8px; display: block; color: #495057;">Batch Size:</label>
                            <select id="batch-size-categories" style="padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; background: white; font-size: 14px; min-width: 200px;">
                                <option value="100">100 categories per batch</option>
                                <option value="500" selected>500 categories per batch</option>
                                <option value="1000">1000 categories per batch</option>
                            </select>
                        </div>
                        
                        <div class="mashry-button-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="button button-primary start-migration-btn" data-type="categories" id="btn-start-migration-categories">
                                🚀 Start Migration
                            </button>
                            
                            <button class="button check-status-btn" data-type="categories" id="btn-check-status-categories">
                                📊 Check Status
                            </button>
                            
                            <button class="button button-secondary reset-migration-btn" data-type="categories" id="btn-reset-migration-categories">
                                🔄 Reset Migration
                            </button>
                            
                            <button class="button download-all-btn" data-type="categories" style="background: #ffc107; color: #212529; border: none; border-radius: 4px; cursor: pointer; padding: 10px 20px; font-size: 14px; font-weight: 500;">
                                📥 Download All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="migration-progress" style="display: none;">
                <h3 style="color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; margin-bottom: 20px;">Migration Progress - <span id="current-type"></span></h3>
                <div style="background: #ddd; height: 20px; border-radius: 10px; margin: 15px 0; overflow: hidden;">
                    <div id="progress-bar" style="background: linear-gradient(90deg, #2271b1, #28a745); height: 100%; width: 0%; transition: width 0.5s ease;"></div>
                </div>
                <div id="progress-text" style="text-align: center; margin: 10px 0; font-size: 16px; font-weight: 600; color: #495057;"></div>
                <div id="batch-status" style="margin: 20px 0;"></div>
            </div>
        </div>
    </div>
    
    <script>
    // Global variables
    let isMigrating = false;
    
    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Set up event listeners
        setupEventListeners();
        
        // Load initial preview
        loadMigrationPreview('products');
    });
    
    // Set up all event listeners
    function setupEventListeners() {
        // Server settings form
        document.getElementById('server-settings-form').addEventListener('submit', handleSettingsSubmit);
        
        // Test connection button
        document.getElementById('test-connection-btn').addEventListener('click', testServerConnection);
        
        // Refresh preview buttons
        document.querySelectorAll('.refresh-preview-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                loadMigrationPreview(type);
            });
        });
        
        // Start migration buttons
        document.querySelectorAll('.start-migration-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                startMigration(type);
            });
        });
        
        // Check status buttons
        document.querySelectorAll('.check-status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                checkMigrationStatus(type);
            });
        });
        
        // Reset migration buttons
        document.querySelectorAll('.reset-migration-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                resetMigration(type);
            });
        });
        
        // Download all buttons
        document.querySelectorAll('.download-all-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                downloadAllData(type);
            });
        });
        
        // Tab switching
        document.querySelectorAll('.mashry-tab-button').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                showTab(tabName);
            });
        });
    }
    
    // Tab switching function
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.mashry-tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        document.querySelectorAll('.mashry-tab-button').forEach(btn => {
            btn.style.background = '#f8f9fa';
            btn.style.borderBottomColor = 'transparent';
            btn.style.fontWeight = '500';
            btn.style.color = '#495057';
        });
        
        // Show selected tab
        document.getElementById(`tab-${tabName}`).style.display = 'block';
        const activeBtn = document.querySelector(`.mashry-tab-button[data-tab="${tabName}"]`);
        activeBtn.style.background = 'white';
        activeBtn.style.borderBottomColor = '#2271b1';
        activeBtn.style.fontWeight = '600';
        activeBtn.style.color = '#2271b1';
        
        // Load preview for the tab
        loadMigrationPreview(tabName);
    }
    
    // Handle server settings form submission
    function handleSettingsSubmit(e) {
        e.preventDefault();
        const serverUrl = document.getElementById('server-url').value;
        
        const formData = new FormData();
        formData.append('action', 'mashry_save_server_settings');
        formData.append('server_url', serverUrl);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
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
    }
    
    // Test server connection
    function testServerConnection() {
        const serverUrl = document.getElementById('server-url').value.trim();
        const testResultEl = document.getElementById('server-test-result');
        
        if (!serverUrl) {
            testResultEl.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; border: 1px solid #f5c6cb;">❌ Please enter a server URL</div>';
            testResultEl.style.display = 'block';
            return;
        }
        
        testResultEl.innerHTML = '<div style="color: #6c757d; padding: 12px; border-radius: 5px;">🔄 Testing connection to: ' + serverUrl + '...</div>';
        testResultEl.style.display = 'block';
        
        fetch('<?php echo rest_url("mashry-connect/v1/test-server"); ?>?server_url=' + encodeURIComponent(serverUrl), {
            headers: {
                'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                testResultEl.innerHTML = '<div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; border: 1px solid #c3e6cb;">✅ Server connection successful!</div>';
            } else {
                testResultEl.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; border: 1px solid #f5c6cb;">❌ Server connection failed: ' + (data.message || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            testResultEl.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; border: 1px solid #f5c6cb;">❌ Error testing connection: ' + error.message + '</div>';
        });
    }
    
    // Load migration preview
    function loadMigrationPreview(type) {
        const previewContent = document.getElementById(`preview-content-${type}`);
        previewContent.innerHTML = '<p style="text-align: center; color: #6c757d;">⏳ Loading preview...</p>';
        
        fetch('<?php echo rest_url("mashry-connect/v1/export/"); ?>' + type + '?action=preview', {
            headers: {
                'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            renderPreview(type, data, previewContent);
        })
        .catch(error => {
            previewContent.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; text-align: center;">❌ Error loading preview</div>';
        });
    }
    
    // Render preview data
    function renderPreview(type, data, previewContent) {
        let html = `
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background: white;">
                <tr>
                    <td style="background: #f8f9fa; font-weight: 600; width: 200px; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Total ${type}:</td>
                    <td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="font-size: 18px; color: #2271b1;">${data.total || 0}</strong></td>
                </tr>
                <tr>
                    <td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Already Migrated:</td>
                    <td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #28a745;">${data.migrated || 0}</strong></td>
                </tr>
                <tr>
                    <td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Pending:</td>
                    <td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #ffc107;">${data.pending || 0}</strong></td>
                </tr>
                <tr>
                    <td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Progress:</td>
                    <td style="padding: 12px; border: 1px solid #dee2e6;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="flex: 1; background: #e9ecef; height: 10px; border-radius: 5px; overflow: hidden;">
                                <div style="height: 100%; background: #28a745; border-radius: 5px; width: ${data.progress_percentage || 0}%"></div>
                            </div>
                            <span style="font-weight: 600; color: #495057;">${data.progress_percentage || 0}%</span>
                        </div>
                    </td>
                </tr>
            </table>
        `;
        
        if (data.sample && data.sample.length > 0) {
            html += `<h4 style="color: #495057; margin: 25px 0 15px 0;">📋 Sample Data (${data.sample.length} items)</h4>`;
            html += renderSampleTable(type, data.sample);
        } else {
            html += `<p style="text-align: center; color: #6c757d; padding: 20px;">No sample data available.</p>`;
        }
        
        previewContent.innerHTML = html;
    }
    
    // Render sample table based on type
    function renderSampleTable(type, sampleData) {
        let headers = '';
        let rows = '';
        
        if (type === 'products') {
            headers = '<tr><th>ID</th><th>Product Name</th><th>Status</th><th>Action</th></tr>';
            sampleData.forEach(item => {
                const statusClass = getStatusClass(item.status);
                rows += `
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;"><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id}</code></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;"><strong>${item.name || 'N/A'}</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; ${statusClass}">
                                ${item.status || 'N/A'}
                            </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;">
                            ${item.edit_url ? 
                                `<a href="${item.edit_url}" target="_blank" style="color: #007bff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 500;">👁️ View</a>` : 
                                '-'
                            }
                        </td>
                    </tr>
                `;
            });
        } else if (type === 'users') {
            headers = '<tr><th>ID</th><th>Username</th><th>Email</th><th>Display Name</th></tr>';
            sampleData.forEach(item => {
                rows += `
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;"><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id}</code></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;"><strong>${item.user_login || 'N/A'}</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;">${item.user_email || 'N/A'}</td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;">${item.display_name || 'N/A'}</td>
                    </tr>
                `;
            });
        } else if (type === 'categories') {
            headers = '<tr><th>ID</th><th>Category Name</th><th>Slug</th><th>Description</th></tr>';
            sampleData.forEach(item => {
                const description = item.description || '';
                const shortDesc = description.length > 50 ? description.substring(0, 50) + '...' : description;
                rows += `
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;"><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id}</code></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;"><strong>${item.name || 'N/A'}</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;"><code>${item.slug || 'N/A'}</code></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #212529;" title="${description}">${shortDesc || '-'}</td>
                    </tr>
                `;
            });
        }
        
        return `<table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 14px; background: white;">${headers}${rows}</table>`;
    }
    
    // Get status class
    function getStatusClass(status) {
        if (status === 'publish') return 'background: #d4edda; color: #155724;';
        if (status === 'draft') return 'background: #f8f9fa; color: #6c757d;';
        if (status === 'private') return 'background: #d1ecf1; color: #0c5460;';
        return '';
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
        document.getElementById('current-type').textContent = type.charAt(0).toUpperCase() + type.slice(1);
        
        // Start migration
        isMigrating = true;
        
        fetch('<?php echo rest_url("mashry-connect/v1/export/"); ?>' + type + '?action=start_migration&batch_size=' + batchSize + '&force_restart=' + forceRestart, {
            headers: {
                'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Migration started! Total items: ' + data.total);
                // Start processing batches
                processNextBatch(type, 1, batchSize);
            } else {
                alert('Failed to start migration: ' + (data.message || 'Unknown error'));
                isMigrating = false;
            }
        })
        .catch(error => {
            alert('Error starting migration: ' + error.message);
            isMigrating = false;
        });
    }
    
    // Process next batch
    function processNextBatch(type, batchNumber, batchSize) {
        if (!isMigrating) return;
        
        updateProgressText(`Processing ${type} batch ${batchNumber}`);
        
        fetch('<?php echo rest_url("mashry-connect/v1/export/"); ?>' + type + '?action=migrate_batch&batch=' + batchNumber + '&batch_size=' + batchSize, {
            headers: {
                'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const migratedCount = data[`${type}_migrated`] || 0;
                const totalItems = data.total_items || 0;
                const totalMigrated = data.total_migrated || 0;
                
                // Calculate progress percentage
                const progressPercentage = totalItems > 0 ? Math.round((totalMigrated / totalItems) * 100) : 0;
                updateProgressBar(progressPercentage);
                
                if (migratedCount > 0) {
                    // Send data to server AND download locally
                    const itemsData = data[`${type}_data`];
                    if (itemsData && itemsData.length > 0) {
                        // 1. Try to send to server
                        sendBatchToServer(type, itemsData, batchNumber);
                        
                        // 2. Always download locally
                        downloadBatchLocally(type, itemsData, batchNumber);
                    }
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
                alert('Batch ' + batchNumber + ' failed: ' + (data.message || 'Unknown error'));
                isMigrating = false;
            }
        })
        .catch(error => {
            alert('Error processing batch ' + batchNumber + ': ' + error.message);
            isMigrating = false;
        });
    }
    
    // Send batch to server
    function sendBatchToServer(type, itemsData, batchNumber) {
        const serverUrl = document.getElementById('server-url').value.trim();
        
        if (!serverUrl) {
            console.log('Server URL is empty. Data will be downloaded locally only.');
            return;
        }
        
        fetch(serverUrl + '/import/' + type, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': '<?php echo MASHRY_CONNECT_API_KEY; ?>'
            },
            body: JSON.stringify(itemsData)
        })
        .then(response => response.json())
        .then(result => {
            console.log('Batch sent to server successfully:', result);
        })
        .catch(error => {
            console.error('Error sending to server:', error);
        });
    }
    
    // Download batch locally
    function downloadBatchLocally(type, itemsData, batchNumber) {
        const dataStr = JSON.stringify(itemsData, null, 2);
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const fileName = `${type}_batch_${batchNumber}_${timestamp}.json`;
        
        downloadJsonFile(dataStr, fileName);
    }
    
    // Download all data
    function downloadAllData(type) {
        fetch('<?php echo rest_url("mashry-connect/v1/export/"); ?>' + type + '?action=get_all', {
            headers: {
                'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            const dataStr = JSON.stringify(data, null, 2);
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const fileName = `${type}_all_${timestamp}.json`;
            
            downloadJsonFile(dataStr, fileName);
            alert(`✅ All ${type} downloaded successfully!`);
        })
        .catch(error => {
            alert(`❌ Error downloading all ${type}: ${error.message}`);
        });
    }
    
    // Download JSON file
    function downloadJsonFile(dataStr, fileName) {
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
    
    // Check migration status
    function checkMigrationStatus(type) {
        fetch('<?php echo rest_url("mashry-connect/v1/export/"); ?>' + type + '?action=migration_status', {
            headers: {
                'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById('batch-status');
            let html = `
                <div style="background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 5px; border: 1px solid #dee2e6;">
                    <h4 style="color: #495057; margin-top: 0;">📈 Current Status</h4>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background: white;">
                        <tr>
                            <td style="background: #f8f9fa; font-weight: 600; width: 200px; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Total:</td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;"><strong>${data.stats.total}</strong></td>
                        </tr>
                        <tr>
                            <td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Migrated:</td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #28a745;">${data.stats.migrated}</strong></td>
                        </tr>
                        <tr>
                            <td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Failed:</td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #dc3545;">${data.stats.failed}</strong></td>
                        </tr>
                        <tr>
                            <td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Progress:</td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;"><strong>${data.progress}%</strong></td>
                        </tr>
                        <tr>
                            <td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Status:</td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: ${data.is_completed ? '#28a745' : '#ffc107'};">${data.is_completed ? '✅ Completed' : '⏳ In Progress'}</strong></td>
                        </tr>
                    </table>
                </div>
            `;
            
            statusDiv.innerHTML = html;
            
            // Update the preview
            loadMigrationPreview(type);
        })
        .catch(error => {
            alert('Error checking status: ' + error.message);
        });
    }
    
    // Reset migration
    function resetMigration(type) {
        if (!confirm('Are you sure you want to reset migration?\n\nThis will delete all tracking data and you will need to start over.')) {
            return;
        }
        
        fetch('<?php echo rest_url("mashry-connect/v1/export/"); ?>' + type + '?action=reset_migration', {
            headers: {
                'Authorization': 'Bearer <?php echo MASHRY_CONNECT_API_KEY; ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`${type.charAt(0).toUpperCase() + type.slice(1)} migration reset successfully!`);
                loadMigrationPreview(type);
                
                // Clear progress
                document.getElementById('migration-progress').style.display = 'none';
                document.getElementById('batch-status').innerHTML = '';
                updateProgressBar(0);
                updateProgressText('');
            } else {
                alert(`Error resetting migration: ${data.message || 'Unknown error'}`);
            }
        })
        .catch(error => {
            alert(`Error resetting migration: ${error.message}`);
        });
    }
    
    // Migration complete
    function migrationComplete(type) {
        isMigrating = false;
        updateProgressBar(100);
        updateProgressText(`${type.charAt(0).toUpperCase() + type.slice(1)} migration completed! 🎉`);
        
        alert(`🎉 ${type.charAt(0).toUpperCase() + type.slice(1)} migration completed successfully!`);
        
        // Final status check
        checkMigrationStatus(type);
    }
    
    function updateProgressBar(percentage) {
        document.getElementById('progress-bar').style.width = percentage + '%';
    }
    
    function updateProgressText(text) {
        document.getElementById('progress-text').innerHTML = text;
    }
    </script>
    <?php
}