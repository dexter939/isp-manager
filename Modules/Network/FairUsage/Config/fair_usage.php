<?php
return [
    'fup_check_interval_minutes' => env('FUP_CHECK_INTERVAL', 5),
    'notification_channels'       => ['email', 'sms'],
    'warning_threshold_percent'   => env('FUP_WARNING_THRESHOLD', 80),
];
