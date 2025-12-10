<?php
// products-export.php

if (!defined('ABSPATH')) {
    exit;
}

function mashry_connect_export_products(WP_REST_Request $request) {
    $params = $request->get_params();
    $action = isset($params['action']) ? sanitize_text_field($params['action']) : 'preview';
    
    switch ($action) {
        case 'preview':
            return mashry_connect_preview_products();
            
        case 'start_migration':
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            $force_restart = isset($params['force_restart']) ? (bool)$params['force_restart'] : false;
            return rest_ensure_response(mashry_connect_start_migration('products', $batch_size, $force_restart));
            
        case 'migrate_batch':
            $batch_number = isset($params['batch']) ? (int)$params['batch'] : 1;
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            $force_restart = isset($params['force_restart']) ? (bool)$params['force_restart'] : false;
            return rest_ensure_response(mashry_connect_migrate_batch('products', $batch_number, $batch_size, $force_restart));
                    
        case 'migration_status':
            return rest_ensure_response(mashry_connect_get_migration_status('products'));
            
        case 'reset_migration':
            return rest_ensure_response(mashry_connect_reset_migration('products'));
            
        case 'get_all':
            return mashry_connect_get_all_products();
            
        default:
            return new WP_Error('invalid_action', 'Invalid action specified', ['status' => 400]);
    }
}

function mashry_connect_preview_products() {
    global $wpdb;
    
    $stats = mashry_connect_get_migration_stats('products');
    
    // Get last 10 products for preview
    $products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title, post_status 
             FROM {$wpdb->posts} 
             WHERE post_type = %s 
             AND post_status IN ('publish', 'draft', 'pending', 'private')
             ORDER BY ID DESC 
             LIMIT 10",
            'product'
        )
    );
    
    $sample = [];
    foreach ($products as $product) {
        $sample[] = [
            'id' => $product->ID,
            'name' => $product->post_title,
            'status' => $product->post_status,
            'edit_url' => admin_url('post.php?post=' . $product->ID . '&action=edit')
        ];
    }
    
    $stats['sample'] = $sample;
    
    return rest_ensure_response($stats);
}

function mashry_connect_get_all_products() {
    global $wpdb;
    
    $products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->posts} 
             WHERE post_type = %s 
             AND post_status IN ('publish', 'draft', 'pending', 'private')
             ORDER BY ID ASC",
            'product'
        )
    );
    
    $products_data = [];
    
    foreach ($products as $product) {
        $product_data = [
            'id' => $product->ID,
            'name' => $product->post_title,
            'slug' => $product->post_name,
            'status' => $product->post_status,
            'description' => $product->post_content,
            'short_description' => $product->post_excerpt,
            'date_created' => $product->post_date,
            'date_modified' => $product->post_modified,
            'type' => get_post_meta($product->ID, '_product_type', true) ?: 'simple',
            'sku' => get_post_meta($product->ID, '_sku', true),
            'price' => get_post_meta($product->ID, '_price', true),
            'regular_price' => get_post_meta($product->ID, '_regular_price', true)
        ];
        
        $products_data[] = $product_data;
    }
    
    return rest_ensure_response($products_data);
}