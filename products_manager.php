<?php
/*
Plugin Name: iBee Products Management Module
Description: A simple plugin to create an API endpoint that updates product stock.
Version: 1.0
Author: Nicolas Dominguez
*/

// Hook to initialize REST API route
add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/update-stock/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_product_stock',

        // Only users with the manage_woocommerce capability can access
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));
});

// Function to update product stock
function update_product_stock($data) {
    $product_id = $data['id'];
    $new_stock = isset($data['stock']) ? intval($data['stock']) : null;

    if (!$new_stock || $new_stock < 0) {
        return new WP_Error('invalid_stock', 'Invalid stock value', array('status' => 400));
    }

    $product = wc_get_product($product_id);

    if (!$product) {
        return new WP_Error('invalid_product', 'Product not found', array('status' => 404));
    }

    // Update stock quantity
    $product->set_stock_quantity($new_stock);
    $product->save();

    return array(
        'product_id' => $product_id,
        'new_stock' => $new_stock,
        'message' => 'Stock updated successfully'
    );
}
