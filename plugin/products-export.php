<?php
// File: products-export.php

/**
 * Export products with pagination/chunking support
 */
function mashry_connect_export_products(WP_REST_Request $request) {
    $params = $request->get_params();
    $action = isset($params['action']) ? sanitize_text_field($params['action']) : 'preview';
    
    switch ($action) {
        case 'preview':
            return mashry_connect_preview_products();
            
        case 'start_migration':
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            $force_restart = isset($params['force_restart']) ? (bool)$params['force_restart'] : false;
            return mashry_connect_start_migration('products', $batch_size, $force_restart);
            
        case 'migrate_batch':
            $batch_number = isset($params['batch']) ? (int)$params['batch'] : 1;
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            return mashry_connect_migrate_batch('products', $batch_number, $batch_size);
            
        case 'migration_status':
            return mashry_connect_get_migration_status('products');
            
        case 'reset_migration':
            return mashry_connect_reset_migration('products');
            
        case 'get_all':
            return mashry_connect_get_all_products();
            
        default:
            return new WP_Error('invalid_action', 'Invalid action specified', array('status' => 400));
    }
}

/**
 * Preview products before migration
 */
function mashry_connect_preview_products() {
    $stats = mashry_connect_get_migration_stats('products');
    return rest_ensure_response($stats);
}

/**
 * Start migration for any type
 */
function mashry_connect_start_migration($type, $batch_size = 500, $force_restart = false) {
    global $wpdb;
    
    if ($force_restart) {
        // Clear existing tracking data
        $wpdb->delete($wpdb->prefix . 'mashry_migration_tracking', 
            array('migration_type' => $type)
        );
    }
    
    // Get total count for planning
    $stats = mashry_connect_get_migration_stats($type);
    $total_items = $stats['total'];
    $total_batches = ceil($total_items / $batch_size);
    
    $response = array(
        'success' => true,
        'message' => "{$type} migration started",
        'batch_size' => $batch_size,
        'total_batches' => $total_batches,
        'next_batch' => 1
    );
    
    // Add type-specific total
    if ($type === 'products') {
        $response['total_products'] = $total_items;
        $response['total'] = $total_items;
    } elseif ($type === 'users') {
        $response['total_users'] = $total_items;
        $response['total'] = $total_items;
    } elseif ($type === 'categories') {
        $response['total_categories'] = $total_items;
        $response['total'] = $total_items;
    }
    
    return rest_ensure_response($response);
}

/**
 * Migrate a batch for any type - FIXED VERSION
 */
function mashry_connect_migrate_batch($type, $batch_number = 1, $batch_size = 500) {
    global $wpdb;
    
    // Get the offset (how many items already migrated)
    $offset = mashry_connect_get_migration_offset($type);
    
    // Get items batch based on type
    $items = array();
    $items_data = array();
    
    if ($type === 'products') {
        // Get products that haven't been migrated yet
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.* FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->prefix}mashry_migration_tracking mt 
                       ON p.ID = mt.item_id AND mt.migration_type = 'products' AND mt.status = 'completed'
                WHERE p.post_type = 'product' 
                AND p.post_status IN ('publish', 'draft', 'private')
                AND mt.item_id IS NULL  -- Only get products not migrated yet
                ORDER BY p.ID ASC
                LIMIT %d",
                $batch_size
            )
        );
        
        foreach ($items as $item) {
            try {
                $item_data = mashry_connect_prepare_product_data($item);
                if ($item_data) {
                    $items_data[] = $item_data;
                    
                    // Mark as migrated
                    mashry_connect_mark_item_migrated($type, $item->ID, $batch_number);
                }
            } catch (Exception $e) {
                mashry_connect_mark_item_migrated($type, $item->ID, $batch_number, $e->getMessage());
            }
        }
        
    } elseif ($type === 'users') {
        // Get users that haven't been migrated yet
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT u.* FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->prefix}mashry_migration_tracking mt 
                       ON u.ID = mt.item_id AND mt.migration_type = 'users' AND mt.status = 'completed'
                WHERE mt.item_id IS NULL  -- Only get users not migrated yet
                ORDER BY u.ID ASC
                LIMIT %d",
                $batch_size
            )
        );
        
        foreach ($items as $item) {
            try {
                $item_data = mashry_connect_prepare_user_data($item);
                if ($item_data) {
                    $items_data[] = $item_data;
                    
                    // Mark as migrated
                    mashry_connect_mark_item_migrated($type, $item->ID, $batch_number);
                }
            } catch (Exception $e) {
                mashry_connect_mark_item_migrated($type, $item->ID, $batch_number, $e->getMessage());
            }
        }
        
    } elseif ($type === 'categories') {
        // Get categories that haven't been migrated yet
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, tt.description, tt.parent FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                LEFT JOIN {$wpdb->prefix}mashry_migration_tracking mt 
                       ON t.term_id = mt.item_id AND mt.migration_type = 'categories' AND mt.status = 'completed'
                WHERE tt.taxonomy = 'product_cat'
                AND mt.item_id IS NULL  -- Only get categories not migrated yet
                ORDER BY t.term_id ASC
                LIMIT %d",
                $batch_size
            )
        );
        
        foreach ($items as $item) {
            try {
                $item_data = mashry_connect_prepare_category_data($item);
                if ($item_data) {
                    $items_data[] = $item_data;
                    
                    // Mark as migrated
                    mashry_connect_mark_item_migrated($type, $item->term_id, $batch_number);
                }
            } catch (Exception $e) {
                mashry_connect_mark_item_migrated($type, $item->term_id, $batch_number, $e->getMessage());
            }
        }
    }
    
    // Check if there are more items
    $stats = mashry_connect_get_migration_stats($type);
    $total_migrated = $stats['migrated'];
    $total_items = $stats['total'];
    $has_more = $total_migrated < $total_items;
    
    $response = array(
        'success' => true,
        'message' => "{$type} batch migration completed",
        'batch_number' => $batch_number,
        'batch_size' => $batch_size,
        'has_more' => $has_more,
        'next_batch' => $has_more ? $batch_number + 1 : null,
        'total_migrated' => $total_migrated,
        'total_items' => $total_items
    );
    
    // Add type-specific data
    if ($type === 'products') {
        $response['products_migrated'] = count($items_data);
        $response['products_data'] = $items_data;
    } elseif ($type === 'users') {
        $response['users_migrated'] = count($items_data);
        $response['users_data'] = $items_data;
    } elseif ($type === 'categories') {
        $response['categories_migrated'] = count($items_data);
        $response['categories_data'] = $items_data;
    }
    
    return rest_ensure_response($response);
}

/**
 * Get migration status for any type
 */
function mashry_connect_get_migration_status($type = 'products') {
    $stats = mashry_connect_get_migration_stats($type);
    
    $progress = $stats['progress_percentage'];
    
    return rest_ensure_response(array(
        'type' => $type,
        'stats' => $stats,
        'progress' => $progress,
        'is_completed' => $stats['migrated'] >= $stats['total']
    ));
}

/**
 * Reset migration for any type
 */
function mashry_connect_reset_migration($type = 'products') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mashry_migration_tracking';
    
    $deleted = $wpdb->delete($table_name, array('migration_type' => $type));
    
    return rest_ensure_response(array(
        'success' => true,
        'message' => "{$type} migration reset",
        'deleted_records' => $deleted
    ));
}

/**
 * Get all products (original function)
 */
function mashry_connect_get_all_products() {
    global $wpdb;
    
    $products = $wpdb->get_results(
        "SELECT * FROM {$wpdb->posts} 
        WHERE post_type = 'product' 
        AND post_status IN ('publish', 'draft', 'private')
        ORDER BY ID ASC"
    );
    
    $products_data = array();
    
    foreach ($products as $product) {
        $product_data = mashry_connect_prepare_product_data($product);
        if ($product_data) {
            $products_data[] = $product_data;
        }
    }
    
    return rest_ensure_response($products_data);
}

/**
 * Prepare product data for export
 */
function mashry_connect_prepare_product_data($product_post) {
    global $wpdb;
    
    $product_id = $product_post->ID;
    
    // Get product meta
    $meta_data = get_post_meta($product_id);
    
    // Get product terms (categories, tags)
    $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
    $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
    
    // Get product type
    $product_type_terms = wp_get_post_terms($product_id, 'product_type', array('fields' => 'names'));
    $product_type = !empty($product_type_terms) ? $product_type_terms[0] : 'simple';
    
    // For variable products, get variations
    $variations = array();
    if ($product_type === 'variable') {
        $variation_posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->posts} 
                WHERE post_type = 'product_variation' 
                AND post_parent = %d
                AND post_status = 'publish'",
                $product_id
            )
        );
        
        foreach ($variation_posts as $variation) {
            $variation_meta = get_post_meta($variation->ID);
            $variations[] = array(
                'id' => $variation->ID,
                'attributes' => isset($variation_meta['_variation_description'][0]) ? $variation_meta['_variation_description'][0] : '',
                'price' => isset($variation_meta['_price'][0]) ? $variation_meta['_price'][0] : '',
                'stock' => isset($variation_meta['_stock'][0]) ? $variation_meta['_stock'][0] : ''
            );
        }
    }
    
    // Get product images
    $images = array();
    $product = wc_get_product($product_id);
    
    if ($product) {
        $featured_image_id = $product->get_image_id();
        if ($featured_image_id) {
            $images[] = wp_get_attachment_url($featured_image_id);
        }
        
        $gallery_image_ids = $product->get_gallery_image_ids();
        foreach ($gallery_image_ids as $image_id) {
            $images[] = wp_get_attachment_url($image_id);
        }
    }
    
    return array(
        'id' => $product_id,
        'name' => $product_post->post_title,
        'description' => $product_post->post_content,
        'short_description' => $product_post->post_excerpt,
        'slug' => $product_post->post_name,
        'status' => $product_post->post_status,
        'date_created' => $product_post->post_date,
        'date_modified' => $product_post->post_modified,
        'type' => $product_type,
        'sku' => isset($meta_data['_sku'][0]) ? $meta_data['_sku'][0] : '',
        'price' => isset($meta_data['_price'][0]) ? $meta_data['_price'][0] : '',
        'regular_price' => isset($meta_data['_regular_price'][0]) ? $meta_data['_regular_price'][0] : '',
        'sale_price' => isset($meta_data['_sale_price'][0]) ? $meta_data['_sale_price'][0] : '',
        'stock_quantity' => isset($meta_data['_stock'][0]) ? $meta_data['_stock'][0] : '',
        'stock_status' => isset($meta_data['_stock_status'][0]) ? $meta_data['_stock_status'][0] : '',
        'weight' => isset($meta_data['_weight'][0]) ? $meta_data['_weight'][0] : '',
        'length' => isset($meta_data['_length'][0]) ? $meta_data['_length'][0] : '',
        'width' => isset($meta_data['_width'][0]) ? $meta_data['_width'][0] : '',
        'height' => isset($meta_data['_height'][0]) ? $meta_data['_height'][0] : '',
        'categories' => $categories,
        'tags' => $tags,
        'attributes' => isset($meta_data['_product_attributes'][0]) ? unserialize($meta_data['_product_attributes'][0]) : array(),
        'variations' => $variations,
        'images' => $images
    );
}

/**
 * Prepare user data for export
 */
function mashry_connect_prepare_user_data($user) {
    $user_meta = get_user_meta($user->ID);
    
    return array(
        'id' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'first_name' => isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '',
        'last_name' => isset($user_meta['last_name'][0]) ? $user_meta['last_name'][0] : '',
        'display_name' => $user->display_name,
        'registered_date' => $user->user_registered,
        'roles' => $user->roles,
        'billing' => array(
            'first_name' => isset($user_meta['billing_first_name'][0]) ? $user_meta['billing_first_name'][0] : '',
            'last_name' => isset($user_meta['billing_last_name'][0]) ? $user_meta['billing_last_name'][0] : '',
            'company' => isset($user_meta['billing_company'][0]) ? $user_meta['billing_company'][0] : '',
            'address_1' => isset($user_meta['billing_address_1'][0]) ? $user_meta['billing_address_1'][0] : '',
            'address_2' => isset($user_meta['billing_address_2'][0]) ? $user_meta['billing_address_2'][0] : '',
            'city' => isset($user_meta['billing_city'][0]) ? $user_meta['billing_city'][0] : '',
            'state' => isset($user_meta['billing_state'][0]) ? $user_meta['billing_state'][0] : '',
            'postcode' => isset($user_meta['billing_postcode'][0]) ? $user_meta['billing_postcode'][0] : '',
            'country' => isset($user_meta['billing_country'][0]) ? $user_meta['billing_country'][0] : '',
            'email' => isset($user_meta['billing_email'][0]) ? $user_meta['billing_email'][0] : $user->user_email,
            'phone' => isset($user_meta['billing_phone'][0]) ? $user_meta['billing_phone'][0] : ''
        ),
        'shipping' => array(
            'first_name' => isset($user_meta['shipping_first_name'][0]) ? $user_meta['shipping_first_name'][0] : '',
            'last_name' => isset($user_meta['shipping_last_name'][0]) ? $user_meta['shipping_last_name'][0] : '',
            'company' => isset($user_meta['shipping_company'][0]) ? $user_meta['shipping_company'][0] : '',
            'address_1' => isset($user_meta['shipping_address_1'][0]) ? $user_meta['shipping_address_1'][0] : '',
            'address_2' => isset($user_meta['shipping_address_2'][0]) ? $user_meta['shipping_address_2'][0] : '',
            'city' => isset($user_meta['shipping_city'][0]) ? $user_meta['shipping_city'][0] : '',
            'state' => isset($user_meta['shipping_state'][0]) ? $user_meta['shipping_state'][0] : '',
            'postcode' => isset($user_meta['shipping_postcode'][0]) ? $user_meta['shipping_postcode'][0] : '',
            'country' => isset($user_meta['shipping_country'][0]) ? $user_meta['shipping_country'][0] : ''
        )
    );
}

/**
 * Prepare category data for export
 */
function mashry_connect_prepare_category_data($category) {
    // Get category image
    $image_id = get_term_meta($category->term_id, 'thumbnail_id', true);
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    
    // Get parent category name if exists
    $parent_name = '';
    if ($category->parent > 0) {
        $parent_term = get_term($category->parent, 'product_cat');
        if ($parent_term && !is_wp_error($parent_term)) {
            $parent_name = $parent_term->name;
        }
    }
    
    return array(
        'id' => $category->term_id,
        'name' => $category->name,
        'slug' => $category->slug,
        'description' => $category->description,
        'parent_id' => $category->parent,
        'parent_name' => $parent_name,
        'image' => $image_url,
        'count' => get_term($category->term_id, 'product_cat')->count
    );
}