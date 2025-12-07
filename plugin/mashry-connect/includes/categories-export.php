<?php
// categories-export.php

if (!defined('ABSPATH')) {
    exit;
}

function mashry_connect_export_categories(WP_REST_Request $request) {
    $params = $request->get_params();
    $action = isset($params['action']) ? sanitize_text_field($params['action']) : 'preview';
    
    switch ($action) {
        case 'preview':
            return mashry_connect_preview_categories();
            
        case 'start_migration':
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            $force_restart = isset($params['force_restart']) ? (bool)$params['force_restart'] : false;
            return rest_ensure_response(mashry_connect_start_migration('categories', $batch_size, $force_restart));
            
        case 'migrate_batch':
            $batch_number = isset($params['batch']) ? (int)$params['batch'] : 1;
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            return rest_ensure_response(mashry_connect_migrate_batch('categories', $batch_number, $batch_size));
            
        case 'migration_status':
            return rest_ensure_response(mashry_connect_get_migration_status('categories'));
            
        case 'reset_migration':
            return rest_ensure_response(mashry_connect_reset_migration('categories'));
            
        case 'get_all':
            return mashry_connect_get_all_categories();
            
        default:
            return new WP_Error('invalid_action', 'Invalid action specified', ['status' => 400]);
    }
}

function mashry_connect_preview_categories() {
    global $wpdb;
    
    $stats = mashry_connect_get_migration_stats('categories');
    
    // Get last 10 categories for preview
    $categories = $wpdb->get_results(
        "SELECT t.term_id, t.name, t.slug, tt.description 
         FROM {$wpdb->terms} t 
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
         WHERE tt.taxonomy = 'product_cat' 
         ORDER BY t.term_id DESC 
         LIMIT 10"
    );
    
    $sample = [];
    foreach ($categories as $category) {
        $sample[] = [
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description
        ];
    }
    
    $stats['sample'] = $sample;
    
    return rest_ensure_response($stats);
}

function mashry_connect_get_all_categories() {
    global $wpdb;
    
    $categories = $wpdb->get_results(
        "SELECT t.*, tt.* 
         FROM {$wpdb->terms} t 
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
         WHERE tt.taxonomy = 'product_cat' 
         ORDER BY t.term_id ASC"
    );
    
    $categories_data = [];
    
    foreach ($categories as $category) {
        $category_data = [
            'id' => (int)$category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'parent' => (int)$category->parent,
            'count' => (int)$category->count
        ];
        
        $categories_data[] = $category_data;
    }
    
    return rest_ensure_response($categories_data);
}