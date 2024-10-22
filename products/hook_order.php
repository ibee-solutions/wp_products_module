<?php

// Define the URL to send order data (using Webhook.site for testing)
define('ORDER_HOOK_URL', 'https://webhook.site/87989437-3b9f-4720-bb05-1d86b7c34816');

// Hook to WooCommerce order completion
add_action('woocommerce_thankyou', 'send_order_to_external_api', 10, 1);

/**
 * Function to send order data to an external URL when an order is placed.
 * 
 * @param int $order_id WooCommerce order ID.
 */
function send_order_to_external_api($order_id) {
    
    $order = wc_get_order($order_id);

    if (!$order) {
        return; // Exit if the order doesn't exist
    }

    // Gather order data
    $order_data = array(
        'order_id' => $order->get_id(),
        'customer' => array(
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'email'      => $order->get_billing_email(),
            'phone'      => $order->get_billing_phone(),
            'address'    => array(
                'line1'   => $order->get_billing_address_1(),
                'line2'   => $order->get_billing_address_2(),
                'city'    => $order->get_billing_city(),
                'postcode'=> $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
            ),
        ),
        'shipping' => array(
            'method' => $order->get_shipping_method(),
            'total'  => $order->get_shipping_total(),
        ),
        'payment_method' => $order->get_payment_method(),
        'products'       => array(),
        'order_total'    => $order->get_total(),
        'order_currency' => $order->get_currency(),
    );

    // Add products to the order data
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();

        $order_data['products'][] = array(
            'product_id'   => $item->get_product_id(),
            'name'         => $item->get_name(),
            'quantity'     => $item->get_quantity(),
            'subtotal'     => $item->get_subtotal(),
            'total'        => $item->get_total(),
            'tax'          => $item->get_total_tax(),
            'sku'          => $product->get_sku(),
        );
    }

    // Prepare arguments for POST request
    $args = array(
        'body'    => json_encode($order_data),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'timeout' => 45,
    );

    // Send order data to the external API
    $response = wp_remote_post(ORDER_HOOK_URL, $args);

    // Check for errors in the response
    if (is_wp_error($response)) {
        // Log or handle the error in your own way
        error_log('Error sending order: ' . $response->get_error_message());
    }
}