<?php

return [
    'name' => 'Core',

    'api_quota' => [
        'warning_threshold' => env('API_QUOTA_WARNING_THRESHOLD', 0.80),
        'block_threshold'   => env('API_QUOTA_BLOCK_THRESHOLD', 0.95),
        'carriers'          => [
            'openfiber' => [
                'daily_limit'  => env('OF_DAILY_QUOTA_LIMIT', 500),
                'call_types'   => ['line_testing', 'order_status', 'ticket_status'],
                'critical'     => ['activation', 'deactivation', 'ticket_open'],
            ],
            'fibercop' => [
                'daily_limit'  => env('FC_DAILY_QUOTA_LIMIT', 1000),
                'call_types'   => ['resource_status', 'degrade_measure', 'traffic_counters'],
                'critical'     => ['activation', 'deactivation', 'ticket_open'],
            ],
            'fastweb' => [
                'daily_limit'  => env('FW_DAILY_QUOTA_LIMIT', 300),
                'call_types'   => ['line_testing', 'order_status'],
                'critical'     => ['activation', 'deactivation'],
            ],
        ],
    ],

    'cache_ttl' => [
        'coverage_address' => env('COVERAGE_ADDRESS_CACHE_TTL', 604800),  // 7 giorni
        'line_test'        => env('LINE_TEST_CACHE_TTL', 21600),           // 6 ore
        'order_status'     => env('ORDER_STATUS_CACHE_TTL', 14400),        // 4 ore
    ],
];
