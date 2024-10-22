<?php

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    return new WP_Error('woocommerce_inactive', 'WooCommerce is not activated', array('status' => 500));
}

function create_product($data) {
    // Extract product data from the request
    $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $description = isset($data['description']) ? sanitize_textarea_field($data['description']) : '';
    $categories = isset($data['categories']) && is_array($data['categories']) ? array_map('intval', $data['categories']) : array();
    $brand = isset($data['brand']) ? intval($data['brand']) : 0;
    $model = isset($data['model']) ? sanitize_text_field($data['model']) : '';
    $stock = isset($data['stock']) ? intval($data['stock']) : 0;
    $price = isset($data['price']) ? floatval($data['price']) : 0.00;
    $tax = isset($data['tax']) ? floatval($data['tax']) : 0.00;

    // Ensure required fields are set
    if (empty($name) || $price <= 0) {
        return new WP_Error('missing_data', 'Product name and price are required', array('status' => 400));
    }

    // Create the WooCommerce product object
    $product = new WC_Product_Simple();

    // Set product data
    $product->set_name($name);
    $product->set_description($description);
    if (!empty($categories)) {
        $product->set_category_ids($categories);
    }
    if (!empty($brand)) {
        $product->update_meta_data('brand', $brand);
    }
    if (!empty($model)) {
        $product->update_meta_data('model', $model);
    }
    $product->set_manage_stock(true); // Enable stock management
    $product->set_stock_quantity($stock);
    $product->set_regular_price($price);
    // TODO: Ask tax policy. This manages taxes through WooCommerce, but it might also be managed just by multiplying this value by the price + 1.
    if ($tax > 0) {
        $product->set_tax_status('taxable');
        $product->set_tax_class('standard');
    } else {
        $product->set_tax_status('none');
    }
    
    // Save the product to the database
    $product_id = $product->save();

    if (!$product_id) {
        return new WP_Error('product_not_created', 'Failed to create product', array('status' => 500));
    }

        // Return success response with product details
        return array(
            'product_id' => $product_id,
            'name' => $name,
            'description' => $description,
            'categories' => $categories,
            'brand' => $brand,
            'model' => $model,
            'price' => $price,
            'stock' => $stock,
            'tax' => $tax,
            'message' => 'Product created successfully'
        );
}

/* USAGE EXAMPLE
PUT /wp-json/wp/v3/products
{
    "name": "Product Name",
    "description": "Product Description",
    "categories": [1, 2],
    "brand": 1,
    "model": "Model",
    "stock": 10,
    "price": 100.00,
    "tax": 0.10
}
*/

/*
TODO:
- When sending a POST request the request should return 405 Method Not Allowed.
*/