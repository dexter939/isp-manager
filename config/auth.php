<?php

return [

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ],
        'portal' => [
            'driver'   => 'session',
            'provider' => 'customers',
        ],
        'agent' => [
            'driver'   => 'session',
            'provider' => 'agents_portal',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],
        'customers' => [
            'driver' => 'eloquent',
            'model'  => App\Models\CustomerPortalUser::class,
        ],
        'agents_portal' => [
            'driver' => 'eloquent',
            'model'  => App\Models\AgentPortalUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
        'customers' => [
            'provider' => 'customers',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
        'agents_portal' => [
            'provider' => 'agents_portal',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800, // 3 hours

];
