<?php
return [
    'default_tariff_plan' => (int) env('CDR_DEFAULT_TARIFF_PLAN', 1),
    'billing_day'         => (int) env('CDR_BILLING_DAY', 28),
    'import_formats' => [
        'asterisk' => ['delimiter' => ',', 'date_format' => 'Y-m-d H:i:s'],
        'yeastar'  => ['delimiter' => ',', 'date_format' => 'd/m/Y H:i:s'],
        'generic'  => ['delimiter' => ',', 'date_format' => 'Y-m-d H:i:s'],
    ],
    'rate_cache_ttl' => 3600,
];
