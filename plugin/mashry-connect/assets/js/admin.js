// mashry Connect Admin JavaScript
(function($) {
    'use strict';
    
    // Global variables
    let isMigrating = false;
    let downloadedFiles = [];
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Set up event listeners
        setupEventListeners();
        
        // Load initial preview
        loadMigrationPreview('products');
    });
    
    // Set up all event listeners
    function setupEventListeners() {
        // Server settings form
        $('#server-settings-form').on('submit', handleSettingsSubmit);
        
        // Test connection button
        $('#test-connection-btn').on('click', testServerConnection);
        
        // Refresh preview buttons
        $('.refresh-preview-btn').on('click', function() {
            const type = $(this).data('type');
            loadMigrationPreview(type);
        });
        
        // Start migration buttons
        $('.start-migration-btn').on('click', function() {
            const type = $(this).data('type');
            startMigration(type);
        });
        
        // Check status buttons
        $('.check-status-btn').on('click', function() {
            const type = $(this).data('type');
            checkMigrationStatus(type);
        });
        
        // Reset migration buttons
        $('.reset-migration-btn').on('click', function() {
            const type = $(this).data('type');
            resetMigration(type);
        });
        
        // Download all buttons
        $('.download-all-btn').on('click', function() {
            const type = $(this).data('type');
            downloadAllData(type);
        });
        
        // Tab switching
        $('.mashry-tab-button').on('click', function() {
            const tabName = $(this).data('tab');
            showTab(tabName);
        });
        
        // Refresh downloads
        $('#refresh-downloads').on('click', refreshDownloads);
        
        // Clear downloads
        $('#clear-downloads').on('click', clearDownloadsList);
        
        // Download report
        $('#download-report').on('click', downloadMigrationReport);
    }
    
    // Tab switching function
    function showTab(tabName) {
        // Hide all tabs
        $('.mashry-tab-content').removeClass('active');
        $('.mashry-tab-button').removeClass('active');
        
        // Show selected tab
        $(`#tab-${tabName}`).addClass('active');
        $(`.mashry-tab-button[data-tab="${tabName}"]`).addClass('active');
        
        // Load preview for the tab
        if (tabName !== 'downloads') {
            loadMigrationPreview(tabName);
        } else {
            refreshDownloads();
        }
    }
    
    // Handle server settings form submission
    function handleSettingsSubmit(e) {
        e.preventDefault();
        const serverUrl = $('#server-url').val();
        
        $.ajax({
            url: mashryConnect.ajax_url,
            method: 'POST',
            data: {
                action: 'mashry_save_server_settings',
                server_url: serverUrl,
                nonce: mashryConnect.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#settings-result').html('<span style="color:green; font-weight: 600;">✓ Settings saved successfully</span>');
                    setTimeout(() => {
                        $('#settings-result').html('');
                    }, 3000);
                } else {
                    $('#settings-result').html('<span style="color:red; font-weight: 600;">✗ Error saving settings</span>');
                }
            },
            error: function() {
                $('#settings-result').html('<span style="color:red; font-weight: 600;">✗ AJAX request failed</span>');
            }
        });
    }
    
    // Test server connection
    function testServerConnection() {
        const serverUrl = $('#server-url').val().trim();
        const testResultEl = $('#server-test-result');
        
        if (!serverUrl) {
            testResultEl.html('<div class="mashry-server-test-error">❌ Please enter a server URL</div>');
            testResultEl.show();
            return;
        }
        
        testResultEl.html('<div style="color: #6c757d;">🔄 Testing connection to: ' + serverUrl + '...</div>');
        testResultEl.show();
        
        $.ajax({
            url: mashryConnect.rest_url + 'test-server',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                server_url: serverUrl
            },
            success: function(data) {
                if (data.success) {
                    testResultEl.html('<div class="mashry-server-test-success">✅ Server connection successful!<br><small>' + data.message + '</small></div>');
                } else {
                    testResultEl.html('<div class="mashry-server-test-error">❌ Server connection failed: ' + (data.message || 'Unknown error') + '</div>');
                }
            },
            error: function(error) {
                testResultEl.html('<div class="mashry-server-test-error">❌ Error testing connection: ' + error.statusText + '</div>');
            }
        });
    }
    
    // Load migration preview
    function loadMigrationPreview(type) {
        const previewContent = $(`#preview-content-${type}`);
        previewContent.html('<p style="text-align: center; color: #6c757d;">⏳ Loading preview...</p>');
        
        $.ajax({
            url: mashryConnect.rest_url + `export/${type}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'preview'
            },
            success: function(data) {
                renderPreview(type, data, previewContent);
            },
            error: function(xhr, status, error) {
                previewContent.html(`
                    <div class="mashry-server-test-error" style="text-align: center;">
                        <strong>❌ Error loading preview:</strong> ${error}
                    </div>
                `);
            }
        });
    }
    
    // Render preview data
    function renderPreview(type, data, previewContent) {
        let html = `
            <table class="mashry-stats-table">
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
                        <div class="mashry-progress-container">
                            <div class="mashry-progress-bar-bg">
                                <div class="mashry-progress-bar-fill" style="width: ${data.progress_percentage || 0}%"></div>
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
                html += renderProductsTable(data.sample);
            } else if (type === 'users') {
                html += renderUsersTable(data.sample);
            } else if (type === 'categories') {
                html += renderCategoriesTable(data.sample);
            }
        } else {
            html += `<p style="text-align: center; color: #6c757d; padding: 20px;">No sample data available.</p>`;
        }
        
        previewContent.html(html);
    }
    
    // Render products table
    function renderProductsTable(sampleData) {
        let html = `
            <table class="mashry-preview-table">
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
        
        sampleData.forEach(function(item) {
            const statusClass = getStatusClass(item.status);
            
            html += `
                <tr>
                    <td><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id}</code></td>
                    <td><strong>${item.name || 'N/A'}</strong></td>
                    <td>
                        <span class="mashry-status-badge ${statusClass}">
                            ${item.status || 'N/A'}
                        </span>
                    </td>
                    <td>
                        ${item.edit_url ? 
                            `<a href="${item.edit_url}" target="_blank" class="mashry-view-link">
                                👁️ View
                            </a>` : 
                            '-'
                        }
                    </td>
                </tr>
            `;
        });
        
        html += `</tbody></table>`;
        return html;
    }
    
    // Render users table
    function renderUsersTable(sampleData) {
        let html = `
            <table class="mashry-preview-table">
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
        
        sampleData.forEach(function(item) {
            html += `
                <tr>
                    <td><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id}</code></td>
                    <td><strong>${item.user_login || 'N/A'}</strong></td>
                    <td>${item.user_email || 'N/A'}</td>
                    <td>${item.display_name || 'N/A'}</td>
                </tr>
            `;
        });
        
        html += `</tbody></table>`;
        return html;
    }
    
    // Render categories table
    function renderCategoriesTable(sampleData) {
        let html = `
            <table class="mashry-preview-table">
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
        
        sampleData.forEach(function(item) {
            const description = item.description || '';
            const shortDesc = description.length > 50 ? description.substring(0, 50) + '...' : description;
            
            html += `
                <tr>
                    <td><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">${item.id}</code></td>
                    <td><strong>${item.name || 'N/A'}</strong></td>
                    <td><code>${item.slug || 'N/A'}</code></td>
                    <td title="${description}">${shortDesc || '-'}</td>
                </tr>
            `;
        });
        
        html += `</tbody></table>`;
        return html;
    }
    
    // Get status class
    function getStatusClass(status) {
        if (status === 'publish') return 'mashry-status-publish';
        if (status === 'draft') return 'mashry-status-draft';
        if (status === 'private') return 'mashry-status-private';
        return '';
    }
    
    // Start migration
    function startMigration(type) {
        if (isMigrating) {
            alert('⚠️ A migration is already in progress!');
            return;
        }
        
        const batchSize = $(`#batch-size-${type}`).val();
        const forceRestart = confirm('Start migration from beginning?\n\nClick OK to restart from the beginning.\nClick Cancel to continue from where you left.');
        
        // Show progress section
        $('#migration-progress').show();
        $('#migration-log').show();
        $('#current-type').text(type.charAt(0).toUpperCase() + type.slice(1));
        
        // Clear log
        $('#log-content').empty();
        
        // Start migration
        isMigrating = true;
        addToLog(`🚀 Starting ${type} migration...`, 'info');
        
        // Disable buttons
        $('.mashry-btn-success').prop('disabled', true);
        
        $.ajax({
            url: mashryConnect.rest_url + `export/${type}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'start_migration',
                batch_size: batchSize,
                force_restart: forceRestart
            },
            success: function(data) {
                if (data.success) {
                    addToLog(`✅ Migration started successfully`, 'success');
                    addToLog(`📊 Total items: ${data.total} | Batch size: ${batchSize}`, 'info');
                    
                    // Start processing batches
                    processNextBatch(type, 1, batchSize);
                } else {
                    addToLog(`❌ Failed to start migration: ${data.message || 'Unknown error'}`, 'error');
                    isMigrating = false;
                    $('.mashry-btn-success').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addToLog(`❌ Error starting migration: ${error}`, 'error');
                isMigrating = false;
                $('.mashry-btn-success').prop('disabled', false);
            }
        });
    }
    
    // Process next batch
    function processNextBatch(type, batchNumber, batchSize) {
        if (!isMigrating) return;
        
        addToLog(`🔄 Processing ${type} batch ${batchNumber}...`, 'info');
        updateProgressText(`Processing ${type} batch ${batchNumber}`);
        
        $.ajax({
            url: mashryConnect.rest_url + `export/${type}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'migrate_batch',
                batch: batchNumber,
                batch_size: batchSize
            },
            success: function(data) {
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
                            sendBatchToServer(type, itemsData, batchNumber);
                            
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
                    $('.mashry-btn-success').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addToLog(`❌ Error processing batch ${batchNumber}: ${error}`, 'error');
                isMigrating = false;
                $('.mashry-btn-success').prop('disabled', false);
            }
        });
    }
    
    // Send batch to server
    function sendBatchToServer(type, itemsData, batchNumber) {
        const serverUrl = $('#server-url').val().trim();
        
        if (!serverUrl) {
            addToLog(`⚠️ Server URL is empty. Data will be downloaded locally only.`, 'warning');
            return;
        }
        
        addToLog(`📤 Sending ${type} batch ${batchNumber} to server at ${serverUrl}...`, 'info');
        
        $.ajax({
            url: serverUrl + '/import/' + type,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': mashryConnect.api_key
            },
            data: JSON.stringify(itemsData),
            success: function(response) {
                addToLog(`✅ ${type.charAt(0).toUpperCase() + type.slice(1)} batch ${batchNumber} sent to server successfully`, 'success');
            },
            error: function(xhr, status, error) {
                addToLog(`❌ Error sending to server: ${error}`, 'error');
            }
        });
    }
    
    // Download batch locally
    function downloadBatchLocally(type, itemsData, batchNumber) {
        const dataStr = JSON.stringify(itemsData, null, 2);
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const fileName = `${type}_batch_${batchNumber}_${timestamp}.json`;
        
        downloadJsonFile(dataStr, fileName);
        
        // Store in downloads list
        downloadedFiles.unshift({
            type: type,
            batch: batchNumber,
            fileName: fileName,
            fileSize: (dataStr.length / 1024).toFixed(2) + ' KB',
            timestamp: new Date().toLocaleString(),
            data: dataStr
        });
        
        // Update downloads tab
        refreshDownloads();
        
        addToLog(`📥 Batch ${batchNumber} downloaded locally: ${fileName}`, 'success');
    }
    
    // Download all data
    function downloadAllData(type) {
        addToLog(`📥 Downloading all ${type}...`, 'info');
        
        $.ajax({
            url: mashryConnect.rest_url + `export/${type}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'get_all'
            },
            success: function(data) {
                const dataStr = JSON.stringify(data, null, 2);
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                const fileName = `${type}_all_${timestamp}.json`;
                
                downloadJsonFile(dataStr, fileName);
                
                // Store in downloads list
                downloadedFiles.unshift({
                    type: type,
                    batch: 'all',
                    fileName: fileName,
                    fileSize: (dataStr.length / 1024).toFixed(2) + ' KB',
                    timestamp: new Date().toLocaleString(),
                    isAllData: true,
                    data: dataStr
                });
                
                // Update downloads tab
                refreshDownloads();
                
                addToLog(`✅ All ${type} downloaded: ${fileName}`, 'success');
                alert(`✅ All ${type} downloaded successfully! File: ${fileName}`);
            },
            error: function(xhr, status, error) {
                addToLog(`❌ Error downloading all ${type}: ${error}`, 'error');
                alert(`❌ Error downloading all ${type}: ${error}`);
            }
        });
    }
    
    // Download JSON file
    function downloadJsonFile(dataStr, fileName) {
        const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', fileName);
        document.body.appendChild(linkElement);
        linkElement.click();
        document.body.removeChild(linkElement);
    }
    
    // Refresh downloads list
    function refreshDownloads() {
        const downloadsContent = $('#downloads-content');
        
        if (downloadedFiles.length === 0) {
            downloadsContent.html('<p style="text-align: center; color: #6c757d;">No downloads yet. Start a migration or use download buttons.</p>');
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
                <div class="mashry-download-item">
                    <div>
                        <div class="mashry-download-filename">
                            ${icon} ${file.fileName}
                        </div>
                        <div style="font-size: 12px; color: #6c757d;">
                            ${file.type} • ${batchLabel} • ${file.timestamp}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="mashry-download-size">${file.fileSize}</div>
                        <a href="#" class="mashry-download-link" data-index="${index}">
                            ⬇️ Download Again
                        </a>
                    </div>
                </div>
            `;
        });
        
        downloadsContent.html(html);
        
        // Add event listeners for download again links
        $('.mashry-download-link').on('click', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            downloadFileAgain(index);
        });
    }
    
    // Download file again
    function downloadFileAgain(index) {
        const file = downloadedFiles[index];
        if (file.data) {
            downloadJsonFile(file.data, file.fileName);
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
            server_url: $('#server-url').val(),
            downloaded_files: downloadedFiles.map(f => ({
                type: f.type,
                batch: f.batch,
                fileName: f.fileName,
                fileSize: f.fileSize,
                timestamp: f.timestamp
            })),
            summary: {
                total_files: downloadedFiles.length,
                products_files: downloadedFiles.filter(f => f.type === 'products').length,
                users_files: downloadedFiles.filter(f => f.type === 'users').length,
                categories_files: downloadedFiles.filter(f => f.type === 'categories').length
            }
        };
        
        const dataStr = JSON.stringify(report, null, 2);
        const fileName = `migration_report_${new Date().getTime()}.json`;
        
        downloadJsonFile(dataStr, fileName);
        
        addToLog(`📊 Migration report downloaded: ${fileName}`, 'success');
    }
    
    // Migration complete
    function migrationComplete(type) {
        isMigrating = false;
        addToLog(`🎉 ${type.charAt(0).toUpperCase() + type.slice(1)} migration completed successfully!`, 'success');
        updateProgressBar(100);
        updateProgressText(`${type.charAt(0).toUpperCase() + type.slice(1)} migration completed! 🎉`);
        
        // Enable buttons
        $('.mashry-btn-success').prop('disabled', false);
        
        // Final status check
        checkMigrationStatus(type);
    }
    
    // Check migration status
    function checkMigrationStatus(type) {
        $.ajax({
            url: mashryConnect.rest_url + `export/${type}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'migration_status'
            },
            success: function(data) {
                const statusDiv = $('#batch-status');
                let html = `
                    <div class="mashry-preview-box">
                        <h4 style="color: #495057; margin-top: 0;">📈 Current Status</h4>
                        <table class="mashry-stats-table">
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
                
                statusDiv.html(html);
                
                // Update the preview
                loadMigrationPreview(type);
            }
        });
    }
    
    // Reset migration
    function resetMigration(type) {
        if (!confirm('Are you sure you want to reset migration?\n\nThis will delete all tracking data and you will need to start over.')) {
            return;
        }
        
        $.ajax({
            url: mashryConnect.rest_url + `export/${type}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'reset_migration'
            },
            success: function(data) {
                if (data.success) {
                    alert(`${type.charAt(0).toUpperCase() + type.slice(1)} migration reset successfully!`);
                    loadMigrationPreview(type);
                    
                    // Clear progress
                    $('#migration-progress').hide();
                    $('#batch-status').empty();
                    updateProgressBar(0);
                    updateProgressText('');
                    $('#log-content').empty();
                } else {
                    alert(`Error resetting migration: ${data.message || 'Unknown error'}`);
                }
            },
            error: function(xhr, status, error) {
                alert(`Error resetting migration: ${error}`);
            }
        });
    }
    
    function updateProgressBar(percentage) {
        $('#progress-bar').css('width', percentage + '%');
    }
    
    function updateProgressText(text) {
        $('#progress-text').text(text);
    }
    
    function addToLog(message, type = 'info') {
        const logContent = $('#log-content');
        const timestamp = new Date().toLocaleTimeString();
        
        let typeClass = 'mashry-log-info';
        if (type === 'error') typeClass = 'mashry-log-error';
        else if (type === 'success') typeClass = 'mashry-log-success';
        else if (type === 'warning') typeClass = 'mashry-log-warning';
        
        logContent.append(`
            <div class="mashry-log-entry">
                <div class="mashry-log-time">[${timestamp}]</div>
                <div class="mashry-log-message ${typeClass}">${message}</div>
            </div>
        `);
        
        // Auto-scroll to bottom
        logContent.scrollTop(logContent[0].scrollHeight);
    }
    
})(jQuery);