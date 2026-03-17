<?php
return [
    'expiry_days'          => env('PROFORMA_EXPIRY_DAYS', 30),
    'reminder_days'        => [7, 15, 25],
    'default_mode'         => env('PROFORMA_DEFAULT_MODE', false),
];
