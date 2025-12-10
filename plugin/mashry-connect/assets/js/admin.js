// mashry Connect Admin JavaScript
// Handles all migration operations with batch processing and change detection
(function($) {
    'use strict';
    
    // Global variables
    let isMigrating = false;
    let downloadedFiles = [];
    let currentForceRestart = false;
    let totalExportedInSession = 0;
    
    // Initialize when DOM is ready
    $(function() {
        setupEventListeners();
        loadMigrationPreview('products');
    });
    
    // Set up all event listeners
    function setupEventListeners() {
        // Server settings form
        $(document).on('submit', '#server-settings-form', handleSettingsSubmit);
        
        // Test connection button
        $(document).on('click', '#test-connection-btn', testServerConnection);
        
        // Refresh preview buttons
        $(document).on('click', '.refresh-preview-btn', function() {
            const type = $(this).data('type');
            loadMigrationPreview(type);
        });
        
        // Start migration buttons
        $(document).on('click', '.start-migration-btn', function() {
            const type = $(this).data('type');
            startMigration(type);
        });
        
        // Force export all buttons - re-export everything
        $(document).on('click', '.force-export-btn', function() {
            const type = $(this).data('type');
            forceExportAll(type);
        });
        
        // Check status buttons
        $(document).on('click', '.check-status-btn', function() {
            const type = $(this).data('type');
            checkMigrationStatus(type);
        });
        
        // Reset migration buttons
        $(document).on('click', '.reset-migration-btn', function() {
            const type = $(this).data('type');
            resetMigration(type);
        });
        
        // Download all buttons
        $(document).on('click', '.download-all-btn', function() {
            const type = $(this).data('type');
            downloadAllData(type);
        });
        
        // Tab switching
        $(document).on('click', '.mashry-tab-button', function() {
            const tabName = $(this).data('tab');
            showTab(tabName);
        });
    }
    
    // Tab switching function
    function showTab(tabName) {
        // Hide all tabs
        $('.mashry-tab-content').hide();
        $('.mashry-tab-button').css({
            'background': '#f8f9fa',
            'border-bottom-color': 'transparent',
            'font-weight': '500',
            'color': '#495057'
        });
        
        // Show selected tab
        $('#tab-' + tabName).show();
        $('.mashry-tab-button[data-tab="' + tabName + '"]').css({
            'background': 'white',
            'border-bottom-color': '#2271b1',
            'font-weight': '600',
            'color': '#2271b1'
        });
        
        // Load preview for the tab
        loadMigrationPreview(tabName);
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
                    setTimeout(function() {
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
            testResultEl.html('<div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; border: 1px solid #f5c6cb;">❌ Please enter a server URL</div>');
            testResultEl.show();
            return;
        }
        
        testResultEl.html('<div style="color: #6c757d; padding: 12px;">🔄 Testing connection to: ' + serverUrl + '...</div>');
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
                    testResultEl.html('<div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; border: 1px solid #c3e6cb;">✅ Server connection successful!</div>');
                } else {
                    testResultEl.html('<div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; border: 1px solid #f5c6cb;">❌ Server connection failed: ' + (data.message || 'Unknown error') + '</div>');
                }
            },
            error: function(error) {
                testResultEl.html('<div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; border: 1px solid #f5c6cb;">❌ Error testing connection: ' + error.statusText + '</div>');
            }
        });
    }
    
    // Load migration preview
    function loadMigrationPreview(type) {
        const previewContent = $('#preview-content-' + type);
        previewContent.html('<p style="text-align: center; color: #6c757d;">⏳ Loading preview...</p>');
        
        $.ajax({
            url: mashryConnect.rest_url + 'export/' + type,
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
                previewContent.html('<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; text-align: center;"><strong>❌ Error loading preview:</strong> ' + error + '</div>');
            }
        });
    }
    
    // Render preview data
    function renderPreview(type, data, previewContent) {
        let html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background: white;">' +
            '<tr><td style="background: #f8f9fa; font-weight: 600; width: 200px; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Total ' + type + ':</td>' +
            '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="font-size: 18px; color: #2271b1;">' + (data.total || 0) + '</strong></td></tr>' +
            '<tr><td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Already Migrated:</td>' +
            '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #28a745;">' + (data.migrated || 0) + '</strong></td></tr>' +
            '<tr><td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Pending:</td>' +
            '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #ffc107;">' + (data.pending || 0) + '</strong></td></tr>' +
            '<tr><td style="background: #f8f9fa; font-weight: 600; color: #495057; padding: 12px; border: 1px solid #dee2e6;">Progress:</td>' +
            '<td style="padding: 12px; border: 1px solid #dee2e6;">' +
            '<div style="display: flex; align-items: center; gap: 10px;">' +
            '<div style="flex: 1; background: #e9ecef; height: 10px; border-radius: 5px; overflow: hidden;">' +
            '<div style="height: 100%; background: #28a745; border-radius: 5px; width: ' + (data.progress_percentage || 0) + '%"></div>' +
            '</div><span style="font-weight: 600; color: #495057;">' + (data.progress_percentage || 0) + '%</span></div>' +
            '</td></tr></table>';
        
        if (data.sample && data.sample.length > 0) {
            html += '<h4 style="color: #495057; margin: 25px 0 15px 0;">📋 Sample Data (' + data.sample.length + ' items)</h4>';
            html += renderSampleTable(type, data.sample);
        } else {
            html += '<p style="text-align: center; color: #6c757d; padding: 20px;">No sample data available.</p>';
        }
        
        previewContent.html(html);
    }
    
    // Render sample data table
    function renderSampleTable(type, sampleData) {
        let headers = '';
        let rows = '';
        
        if (type === 'products') {
            headers = '<tr><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">ID</th><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">Product Name</th><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">Status</th></tr>';
            $.each(sampleData, function(i, item) {
                const statusColor = item.status === 'publish' ? '#d4edda' : '#f8f9fa';
                const statusTextColor = item.status === 'publish' ? '#155724' : '#6c757d';
                rows += '<tr><td style="padding: 10px; border: 1px solid #dee2e6;"><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">' + item.id + '</code></td>' +
                    '<td style="padding: 10px; border: 1px solid #dee2e6;"><strong>' + (item.name || 'N/A') + '</strong></td>' +
                    '<td style="padding: 10px; border: 1px solid #dee2e6;"><span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: ' + statusColor + '; color: ' + statusTextColor + ';">' + (item.status || 'N/A') + '</span></td></tr>';
            });
        } else if (type === 'users') {
            headers = '<tr><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">ID</th><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">Username</th><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">Email</th><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">Display Name</th></tr>';
            $.each(sampleData, function(i, item) {
                rows += '<tr><td style="padding: 10px; border: 1px solid #dee2e6;"><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">' + item.id + '</code></td>' +
                    '<td style="padding: 10px; border: 1px solid #dee2e6;"><strong>' + (item.username || 'N/A') + '</strong></td>' +
                    '<td style="padding: 10px; border: 1px solid #dee2e6;">' + (item.email || 'N/A') + '</td>' +
                    '<td style="padding: 10px; border: 1px solid #dee2e6;">' + (item.display_name || 'N/A') + '</td></tr>';
            });
        } else if (type === 'categories') {
            headers = '<tr><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">ID</th><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">Category Name</th><th style="padding: 10px; border: 1px solid #dee2e6; background: #f8f9fa; font-weight: 600;">Slug</th></tr>';
            $.each(sampleData, function(i, item) {
                rows += '<tr><td style="padding: 10px; border: 1px solid #dee2e6;"><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">' + item.id + '</code></td>' +
                    '<td style="padding: 10px; border: 1px solid #dee2e6;"><strong>' + (item.name || 'N/A') + '</strong></td>' +
                    '<td style="padding: 10px; border: 1px solid #dee2e6;"><code>' + (item.slug || 'N/A') + '</code></td></tr>';
            });
        }
        
        return '<table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 14px; background: white;"><thead>' + headers + '</thead><tbody>' + rows + '</tbody></table>';
    }
    
    // Start normal migration (incremental with change detection)
    function startMigration(type) {
        if (isMigrating) {
            alert('⚠️ A migration is already in progress!');
            return;
        }
        
        const batchSize = $('#batch-size-' + type).val() || 500;
        const continueFromWhere = confirm('Continue from where you left off?\n\nClick OK to continue from previous position.\nClick Cancel to start from the beginning.');
        
        performMigration(type, batchSize, !continueFromWhere);
    }
    
    // Force export all items regardless of change detection
    function forceExportAll(type) {
        if (isMigrating) {
            alert('⚠️ A migration is already in progress!');
            return;
        }
        
        if (!confirm('Force re-export all ' + type + '?\n\nThis will re-export every ' + type + ' item even if data hasn\'t changed.\n\nAre you sure?')) {
            return;
        }
        
        const batchSize = $('#batch-size-' + type).val() || 500;
        performMigration(type, batchSize, true);
    }
    
    // Perform the actual migration
    function performMigration(type, batchSize, forceExport) {
        // Show progress section
        $('#migration-progress').show();
        $('#current-type').text(type.charAt(0).toUpperCase() + type.slice(1));
        
        isMigrating = true;
        currentForceRestart = forceExport;
        totalExportedInSession = 0;
        
        // Update progress text
        if (forceExport) {
            updateProgressText('⚡ Force exporting all ' + type + '...');
        } else {
            updateProgressText('🚀 Starting migration of ' + type + '...');
        }
        
        $.ajax({
            url: mashryConnect.rest_url + 'export/' + type,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'start_migration',
                batch_size: batchSize,
                force_restart: forceExport
            },
            success: function(data) {
                if (data.success) {
                    updateProgressText('Total items to process: ' + data.total);
                    processNextBatch(type, 1, batchSize, forceExport);
                } else {
                    alert('Failed to start migration: ' + (data.message || 'Unknown error'));
                    isMigrating = false;
                }
            },
            error: function(xhr, status, error) {
                alert('Error starting migration: ' + error);
                isMigrating = false;
            }
        });
    }
    
// Process next batch in the migration
function processNextBatch(type, batchNumber, batchSize, forceExport) {
    $.ajax({
        url: mashryConnect.rest_url + 'export/' + type,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + mashryConnect.api_key
        },
        data: {
            action: 'migrate_batch',
            batch: batchNumber,
            batch_size: batchSize,
            force_restart: forceExport
        },
        success: function(data) {
            console.log('Full response:', data);
            
            const itemCountKey = type + '_migrated';
            const itemCount = parseInt(data[itemCountKey]) || 0;
            const totalMigratedSoFar = parseInt(data.total_migrated) || 0;
            const hasMore = data.has_more === true;
            
            console.log('Batch', batchNumber, '- Items in batch:', itemCount, '- Total migrated:', totalMigratedSoFar, '- Has more:', hasMore);
            
            if (data.success) {
                totalExportedInSession = totalMigratedSoFar;
                
                console.log('Updated total exported:', totalExportedInSession);
                
                if (hasMore) {
                    updateProgressBar((batchNumber * 20) % 100);
                    updateProgressText('Processing batch ' + batchNumber + ' (exported: ' + totalExportedInSession + ')');
                    
                    setTimeout(function() {
                        processNextBatch(type, batchNumber + 1, batchSize, forceExport);
                    }, 1000);
                } else {
                    console.log('Migration completed. Total exported:', totalExportedInSession);
                    migrationComplete(type, totalExportedInSession);
                }
            } else {
                console.error('Server error:', data.message || 'Unknown error');
                isMigrating = false;
                alert('Error during migration: ' + (data.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error processing batch:', error);
            isMigrating = false;
            alert('Error during migration: ' + error);
        }
    });
}
    
    // Download batch locally
    function downloadBatchLocally(type, itemsData, batchNumber) {
        const dataStr = JSON.stringify(itemsData, null, 2);
        const fileName = type + '_batch_' + batchNumber + '.json';
        
        // Delay download to prevent race conditions
        setTimeout(function() {
            downloadJsonFile(dataStr, fileName);
        }, 100);
    }
    
    // Download all data
    function downloadAllData(type) {
        $.ajax({
            url: mashryConnect.rest_url + 'export/' + type,
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
                const fileName = type + '_all_' + timestamp + '.json';
                
                downloadJsonFile(dataStr, fileName);
                alert('✅ All ' + type + ' downloaded successfully!');
            },
            error: function(error) {
                alert('❌ Error downloading all ' + type + ': ' + error.statusText);
            }
        });
    }
    
    // Create blob and trigger download
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
        $.ajax({
            url: mashryConnect.rest_url + 'export/' + type,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'migration_status'
            },
            success: function(data) {
                const statusHtml = '<div style="background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 5px; border: 1px solid #dee2e6;">' +
                    '<h4 style="color: #495057; margin-top: 0;">📈 Current Status</h4>' +
                    '<table style="width: 100%; border-collapse: collapse; background: white;">' +
                    '<tr><td style="background: #f8f9fa; font-weight: 600; width: 200px; padding: 12px; border: 1px solid #dee2e6;">Total:</td>' +
                    '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong>' + data.stats.total + '</strong></td></tr>' +
                    '<tr><td style="background: #f8f9fa; font-weight: 600; padding: 12px; border: 1px solid #dee2e6;">Migrated:</td>' +
                    '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #28a745;">' + data.stats.migrated + '</strong></td></tr>' +
                    '<tr><td style="background: #f8f9fa; font-weight: 600; padding: 12px; border: 1px solid #dee2e6;">Failed:</td>' +
                    '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: #dc3545;">' + data.stats.failed + '</strong></td></tr>' +
                    '<tr><td style="background: #f8f9fa; font-weight: 600; padding: 12px; border: 1px solid #dee2e6;">Progress:</td>' +
                    '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong>' + data.progress + '%</strong></td></tr>' +
                    '<tr><td style="background: #f8f9fa; font-weight: 600; padding: 12px; border: 1px solid #dee2e6;">Status:</td>' +
                    '<td style="padding: 12px; border: 1px solid #dee2e6;"><strong style="color: ' + (data.is_completed ? '#28a745' : '#ffc107') + ';">' + (data.is_completed ? '✅ Completed' : '⏳ In Progress') + '</strong></td></tr>' +
                    '</table></div>';
                
                $('#batch-status').html(statusHtml);
                loadMigrationPreview(type);
            },
            error: function(error) {
                alert('Error checking status: ' + error.statusText);
            }
        });
    }
    
    // Reset migration
    function resetMigration(type) {
        if (!confirm('Clear all ' + type + ' migration history?\n\nThis will delete all tracking data. Next migration will treat all items as new.\n\nAre you sure?')) {
            return;
        }
        
        $.ajax({
            url: mashryConnect.rest_url + 'export/' + type,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + mashryConnect.api_key
            },
            data: {
                action: 'reset_migration'
            },
            success: function(data) {
                if (data.success) {
                    alert(type.charAt(0).toUpperCase() + type.slice(1) + ' migration history cleared successfully!');
                    loadMigrationPreview(type);
                    
                    // Clear progress display
                    $('#migration-progress').hide();
                    $('#batch-status').html('');
                    updateProgressBar(0);
                    updateProgressText('');
                } else {
                    alert('Error clearing history: ' + (data.message || 'Unknown error'));
                }
            },
            error: function(error) {
                alert('Error clearing history: ' + error.statusText);
            }
        });
    }
    
// Mark migration as complete
function migrationComplete(type, totalMigrated) {
    isMigrating = false;
    updateProgressBar(100);
    updateProgressText('✅ ' + type.charAt(0).toUpperCase() + type.slice(1) + ' migration completed!');
    
    // Clear the force export flag from backend
    $.ajax({
        url: mashryConnect.rest_url + 'export/' + type,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + mashryConnect.api_key
        },
        data: {
            action: 'clear_force_export_flag'
        },
        error: function() {
            // Silently fail - not critical
        }
    });
    
    if (totalExportedInSession > 0) {
        alert('🎉 Migration completed!\n\nExported: ' + totalExportedInSession + ' ' + type);
    } else {
        alert('✓ Migration completed.\n\nExported: 0 ' + type);
    }
    
    checkMigrationStatus(type);
}
    // UI Update Functions
    function updateProgressBar(percentage) {
        $('#progress-bar').css('width', percentage + '%');
    }
    
    function updateProgressText(text) {
        $('#progress-text').html(text);
    }

})(jQuery);