<?php

/* TODO
Path: /
Description: Con un PUT debe crear con los datos básico disponibles:
los datos basico son
- nombre
- descripcion
- categorias
- marca
- modelo
- stock
- precio
- impuesto
*/

function create_product($data) {
    // Extract product data from the request
    $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $description = isset($data['description']) ? sanitize_textarea_field($data['description']) : '';
    $categories = isset($data['categories']) ? array_map($data['categories']) : '';
    $brand = isset($data['brand']) ? sanitize_text_field($data['brand']) : '';
    $model = isset($data['model']) ? sanitize_text_field($data['model']) : '';
    $stock = isset($data['stock']) ? intval($data['stock']) : '';
    $price = isset($data['price']) ? floatval($data['price']) : '';
    $tax = isset($data['tax']) ? floatval($data['tax']) : '';

    // Ensure required fields are set
    if (empty($name) || $price <= 0) {
        return new WP_Error('missing_data', 'Product name and price are required', array('status' => 400));
    }

    // Create the product
    $product = wc_create_product();
    $product->set_name($name);
    $product->set_description($description);
    $product->set_categories($categories);
    $product->set_brand($brand);
    $product->set_model($model);
    
    
}