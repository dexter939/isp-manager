<?php

declare(strict_types=1);

return [
    'currency'                            => 'EUR',
    'low_balance_threshold_default_cents' => env('PREPAID_LOW_BALANCE_THRESHOLD', 500),
    'auto_suspend_on_zero'                => env('PREPAID_AUTO_SUSPEND', true),
    'billing_job_time'                    => '02:00',
    'grace_period_hours'                  => 24,
    'paypal_client_id'                    => env('PAYPAL_CLIENT_ID'),
    'paypal_client_secret'                => env('PAYPAL_CLIENT_SECRET'),
    'paypal_mode'                         => env('PAYPAL_MODE', 'sandbox'),
    'reseller_commission_default_type'    => 'percentage',
    'reseller_commission_default_value'   => 1000, // 10% in basis points
];
