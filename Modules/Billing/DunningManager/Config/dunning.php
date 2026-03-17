<?php

declare(strict_types=1);

return [
    'run_at'         => env('DUNNING_RUN_AT', '08:00'),
    'grace_days'     => env('DUNNING_GRACE_DAYS', 3),
    'default_policy' => [
        'steps' => [
            ['day' => 3,  'action' => 'email',     'template' => 'first_reminder'],
            ['day' => 7,  'action' => 'sms',        'template' => 'urgent_reminder'],
            ['day' => 14, 'action' => 'suspend',    'penalty_cents' => 500],
            ['day' => 30, 'action' => 'terminate'],
        ],
    ],
];
