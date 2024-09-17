# wp_products_module
A WordPress plugin that offers a series of endpoints for managing products.

**Usage:**


_POST_: `wp-json/wc/v3/update-stock/<product_id>`

Body:
```
{
  "stock": <integer>
}
```

Note: You need to include consumer key and consumer secret values since WooCommerce API requires OAuth 1.0 authentication. This values are generated through the WooCommerce advanced settings.
