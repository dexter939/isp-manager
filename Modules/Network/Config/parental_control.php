<?php

declare(strict_types=1);

return [
    'resolver_driver'      => env('PC_RESOLVER_DRIVER', 'whalebone'),
    'whalebone_api_url'    => env('WHALEBONE_API_URL'),
    'whalebone_api_key'    => env('WHALEBONE_API_KEY'),
    'dns_proxy_primary'    => env('DNS_PROXY_PRIMARY', '8.8.8.8'),
    'dns_proxy_secondary'  => env('DNS_PROXY_SECONDARY', '8.8.4.4'),
    'agcom_list_url'       => env('AGCOM_LIST_URL'),
    'log_retention_months' => 6,
    'default_profile_id'   => env('PC_DEFAULT_PROFILE_ID'),
];
