<?php
/**
 * Send batch data to Node.js server
 * Sends exported batch data to the configured server URL
 * Also creates and saves a local JSON file as backup
 * 
 * @param string $type Migration type (users, products, categories)
 * @param array $items Items data to send
 * @param int $batch_number Current batch number
 * @return array Response with success status and details
 */
function mashry_connect_send_batch_to_server($type, $items, $batch_number) {
    // Get server URL from settings
    $server_url = get_option('mashry_node_server_url', '');
    
    // Save to local file as backup FIRST regardless of server status
    $backup_file = mashry_connect_save_batch_to_file($type, $items, $batch_number);
    
    if (empty($server_url)) {
        error_log('[Mashry Connect] Server URL not configured. Data saved locally at: ' . $backup_file);
        return [
            'success' => false,
            'message' => 'Server URL not configured',
            'backup_file' => $backup_file,
            'items_saved_locally' => count($items)
        ];
    }
    
    if (empty($items)) {
        return [
            'success' => false,
            'message' => 'No items to send'
        ];
    }
    
    // Prepare batch data
    $batch_data = [
        'type' => $type,
        'batch_number' => $batch_number,
        'item_count' => count($items),
        'timestamp' => current_time('mysql'),
        'items' => $items
    ];
    
    // Build full server URL
    $endpoint_url = rtrim($server_url, '/') . '/api/migrate/' . $type;
    
    error_log('[Mashry Connect] Sending batch to: ' . $endpoint_url);
    
    // Send to Node.js server via POST request
    $response = wp_remote_post(
        $endpoint_url,
        [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($batch_data),
            'timeout' => 30,
            'sslverify' => false,
            'blocking' => true
        ]
    );
    
    // Check for connection errors
    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
        error_log('[Mashry Connect] Server connection failed: ' . $error_msg);
        return [
            'success' => false,
            'message' => 'Server connection failed: ' . $error_msg,
            'backup_file' => $backup_file,
            'items_saved_locally' => count($items)
        ];
    }
    
    // Get response code and body
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    error_log('[Mashry Connect] Server response code: ' . $response_code);
    error_log('[Mashry Connect] Server response body: ' . substr($response_body, 0, 500));
    
    if (in_array($response_code, [200, 201])) {
        error_log('[Mashry Connect] Batch ' . $batch_number . ' sent successfully to server');
        return [
            'success' => true,
            'message' => 'Batch sent to server successfully',
            'server_response' => $response_body,
            'backup_file' => $backup_file,
            'items_sent' => count($items)
        ];
    } else {
        error_log('[Mashry Connect] Server error - Status: ' . $response_code);
        return [
            'success' => false,
            'message' => 'Server returned error code: ' . $response_code,
            'response' => substr($response_body, 0, 500),
            'backup_file' => $backup_file,
            'items_saved_locally' => count($items)
        ];
    }
}

/**
 * Save batch data to local JSON file
 * Creates a timestamped backup file for each batch
 * 
 * @param string $type Migration type
 * @param array $items Items data
 * @param int $batch_number Batch number
 * @return string File path of saved batch
 */
function mashry_connect_save_batch_to_file($type, $items, $batch_number) {
    // Create data directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $data_dir = $upload_dir['basedir'] . '/mashry-connect-exports';
    
    if (!is_dir($data_dir)) {
        wp_mkdir_p($data_dir);
    }
    
    // Create type-specific directory
    $type_dir = $data_dir . '/' . $type;
    if (!is_dir($type_dir)) {
        wp_mkdir_p($type_dir);
    }
    
    // Create timestamped filename
    $timestamp = date('Y-m-d_H-i-s');
    $filename = $type . '_batch_' . $batch_number . '_' . $timestamp . '.json';
    $filepath = $type_dir . '/' . $filename;
    
    // Prepare batch data structure
    $batch_data = [
        'type' => $type,
        'batch_number' => $batch_number,
        'item_count' => count($items),
        'exported_at' => current_time('mysql'),
        'items' => $items
    ];
    
    // Write to file
    $json_data = json_encode($batch_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filepath, $json_data) !== false) {
        error_log('[Mashry Connect] Batch saved locally: ' . $filepath);
        return $filepath;
    } else {
        error_log('[Mashry Connect] Failed to save batch locally: ' . $filepath);
        return '';
    }
}