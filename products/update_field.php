<?php
/* TODO
Path: /<product_id>/<field>/<value>
Description: Con un PATCH debe actualizar solo el campo con el valor enviado 
*/

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    return new WP_Error('woocommerce_inactive', 'WooCommerce is not activated', array('status' => 500));
}

function update_field($request) {
    // Get the product ID, field, and value from the request
    $product_id = intval($request['id']);
    $field = sanitize_text_field($request['field']);
    $value = sanitize_text_field($request['value']);

    // Get the product by ID
    $product = wc_get_product($product_id);

    // Check if the product exists
    if (!$product) {
        return new WP_Error('invalid_product', 'Product not found', array('status' => 404));
    }

    // Define the valid fields that can be updated
    $updatable_fields = array(
        'name' => 'set_name',
        'description' => 'set_description',
        'price' => 'set_regular_price',
        'stock' => 'set_stock_quantity',
        'categories' => 'set_category_ids',
        'brand' => 'brand',
        'model' => 'model',
        'tax' => 'tax'
    );

    // Check if the field is valid
    if (!array_key_exists($field, $updatable_fields)) {
        return new WP_Error('invalid_field', 'Invalid field specified', array('status' => 400));
    }

    // Update the field based on its type
    switch ($field) {
        case 'name':
        case 'description':
            $product->{$updatable_fields[$field]}(sanitize_text_field($value));
            break;
        case 'price':
            $product->{$updatable_fields[$field]}(floatval($value));
            break;
        case 'stock':
            $product->{$updatable_fields[$field]}(intval($value));
            break;
        case 'categories':
            $category_ids = array_map('intval', explode(',', $value));
            $product->set_category_ids($category_ids);
            break;
        case 'brand':
            $product->update_meta_data('brand', intval($value));
            break;
        case 'model':
            $product->update_meta_data($field, sanitize_text_field($value));
            break;
        case 'tax':
            if (floatval($value) > 0) {
                $product->set_tax_status('taxable');
                $product->set_tax_class('standard');
            } else {
                $product->set_tax_status('none');
            }
            break;
        default:
            return new WP_Error('invalid_update', 'Field not updatable', array('status' => 400));
    }

    // Save the product after updating the field
    $product_id = $product->save();

    if (!$product_id) {
        return new WP_Error('product_not_updated', 'Failed to update product field', array('status' => 500));
    }

    // Return success response
    return array(
        'product_id' => $product_id,
        'updated_field' => $field,
        'new_value' => $value,
        'message' => 'Field updated successfully'
    );
}

// Register REST route for updating a specific field of a product
add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/products/(?P<id>\d+)/(?P<field>[a-zA-Z0-9_]+)/(?P<value>[a-zA-Z0-9_.,-]+)', array(
        'methods' => 'PATCH',
        'callback' => 'update_field',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));
});

/* USAGE EXAMPLE
PATCH /wp-json/wc/v3/products/123/price/19.99
PATCH /wp-json/wc/v3/products/123/stock/50
PATCH /wp-json/wc/v3/products/123/name/New Product Name
PATCH /wp-json/wc/v3/products/123/categories/1,2
PATCH /wp-json/wc/v3/products/123/brand/2
*/
