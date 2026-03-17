<?php

return [
    'default_gateway' => env('PAYMENT_GATEWAY', 'stripe'),
    'stripe' => [
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
        'secret_key'      => env('STRIPE_SECRET_KEY', ''),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET', ''),
        'currency'        => 'eur',
    ],
    'nexi' => [
        'alias'    => env('NEXI_ALIAS', ''),
        'api_key'  => env('NEXI_API_KEY', ''),
        'base_url' => env('NEXI_BASE_URL', 'https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet'),
        'currency' => '978', // EUR ISO 4217 numeric
        'mac_key'  => env('NEXI_MAC_KEY', ''),
    ],
];
