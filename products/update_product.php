<?php

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    return new WP_Error('woocommerce_inactive', 'WooCommerce is not activated', array('status' => 500));
}

function update_product($request) {
    // Get the product ID from the request URL
    $product_id = $request['id'];
    $product = wc_get_product($product_id);

    // Check if the product exists
    if (!$product) {
        return new WP_Error('invalid_product', 'Product not found', array('status' => 404));
    }

    // Extract product data from the request
    $name = isset($request['name']) ? sanitize_text_field($request['name']) : '';
    $description = isset($request['description']) ? sanitize_textarea_field($request['description']) : '';
    $categories = isset($request['categories']) && is_array($request['categories']) ? array_map('intval', $request['categories']) : array();
    $brand = isset($request['brand']) ? intval($request['brand']) : 0;
    $model = isset($request['model']) ? sanitize_text_field($request['model']) : '';
    $stock = isset($request['stock']) ? intval($request['stock']) : null;
    $price = isset($request['price']) ? floatval($request['price']) : null;
    $tax = isset($request['tax']) ? floatval($request['tax']) : null;

    // Update the WooCommerce product data (only if the fields are provided)
    if (!empty($name)) {
        $product->set_name($name);
    }
    if (!empty($description)) {
        $product->set_description($description);
    }
    if (!empty($categories)) {
        $product->set_category_ids($categories);
    }
    if (!empty($brand)) {
        $product->update_meta_data('brand', $brand);
    }
    if (!empty($model)) {
        $product->update_meta_data('model', $model);
    }
    if ($stock !== null) {
        $product->set_manage_stock(true); // Enable stock management
        $product->set_stock_quantity($stock);
    }
    if ($price !== null) {
        $product->set_regular_price($price);
    }

    // Handle tax
    if ($tax !== null) {
        if ($tax > 0) {
            $product->set_tax_status('taxable');
            $product->set_tax_class('standard');
        } else {
            $product->set_tax_status('none');
        }
    }

    // Save the updated product
    $product_id = $product->save();

    if (!$product_id) {
        return new WP_Error('product_not_updated', 'Failed to update product', array('status' => 500));
    }

    // Return success response with updated product details
    return array(
        'product_id' => $product_id,
        'name' => $product->get_name(),
        'description' => $product->get_description(),
        'categories' => $categories,
        'brand' => $brand,
        'model' => $model,
        'price' => $product->get_regular_price(),
        'stock' => $product->get_stock_quantity(),
        'tax' => $tax,
        'message' => 'Product updated successfully'
    );
}

// Register REST route for updating product
add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/products/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_product',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));
});

/* USAGE EXAMPLE
POST /wp-json/wc/v3/products/<product_id>
{
    "name": "Updated Product Name",
    "description": "Updated Product Description",
    "categories": [1, 2],
    "brand": 1,
    "model": "Updated Model",
    "stock": 5,
    "price": 150.00,
    "tax": 0.21
}
*/
