<?php

declare(strict_types=1);

return [
    'offline_threshold_minutes' => env('FLOATING_IP_OFFLINE_THRESHOLD', 5),
    'grace_period_minutes'      => env('FLOATING_IP_GRACE_PERIOD', 2),
    'auto_recovery_enabled'     => env('FLOATING_IP_AUTO_RECOVERY', true),
    'monitor_interval_seconds'  => 60,
    'notify_channels'           => ['mail'],
];
