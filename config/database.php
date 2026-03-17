<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('DATABASE_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '5432'),
            'database'       => env('DB_DATABASE', 'ispmanager'),
            'username'       => env('DB_USERNAME', 'ispmanager'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => env('DB_SCHEMA', 'public'),
            'sslmode'        => env('DB_SSLMODE', 'prefer'),
            'options'        => [
                // Necessario per PostGIS
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ],
    ],

    'migrations' => [
        'table'  => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        // REDIS_CLIENT=predis (dev singolo) | predis (prod Sentinel — configurare .env.production)
        'client' => env('REDIS_CLIENT', 'predis'),

        // ── Modalità Sentinel (produzione) ───────────────────────────────────
        // Attivare impostando REDIS_SENTINEL=true in .env.production
        // Richiede: predis/predis con supporto Sentinel
        // Env vars:
        //   REDIS_SENTINEL=true
        //   REDIS_SENTINEL_MASTER=ispmaster
        //   REDIS_SENTINEL_HOST_1=redis-sentinel-1
        //   REDIS_SENTINEL_HOST_2=redis-sentinel-2
        //   REDIS_SENTINEL_HOST_3=redis-sentinel-3
        //   REDIS_SENTINEL_PORT=26379
        //   REDIS_PASSWORD=secret

        'options' => env('REDIS_SENTINEL', false) ? [
            'replication' => 'sentinel',
            'service'     => env('REDIS_SENTINEL_MASTER', 'ispmaster'),
            'parameters'  => [
                'password' => env('REDIS_PASSWORD'),
                'database' => 0,
            ],
        ] : [],

        'default' => env('REDIS_SENTINEL', false) ? [
            // Predis Sentinel: lista host Sentinel
            ['host' => env('REDIS_SENTINEL_HOST_1', 'redis-sentinel-1'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_2', 'redis-sentinel-2'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_3', 'redis-sentinel-3'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
        ] : [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => env('REDIS_SENTINEL', false) ? [
            ['host' => env('REDIS_SENTINEL_HOST_1', 'redis-sentinel-1'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_2', 'redis-sentinel-2'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_3', 'redis-sentinel-3'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            'options' => ['parameters' => ['database' => env('REDIS_CACHE_DB', 1)]],
        ] : [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

        'queue' => env('REDIS_SENTINEL', false) ? [
            ['host' => env('REDIS_SENTINEL_HOST_1', 'redis-sentinel-1'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_2', 'redis-sentinel-2'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_3', 'redis-sentinel-3'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            'options' => ['parameters' => ['database' => env('REDIS_QUEUE_DB', 2)]],
        ] : [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '2'),
        ],

        'session' => env('REDIS_SENTINEL', false) ? [
            ['host' => env('REDIS_SENTINEL_HOST_1', 'redis-sentinel-1'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_2', 'redis-sentinel-2'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            ['host' => env('REDIS_SENTINEL_HOST_3', 'redis-sentinel-3'), 'port' => env('REDIS_SENTINEL_PORT', 26379)],
            'options' => ['parameters' => ['database' => env('REDIS_SESSION_DB', 3)]],
        ] : [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '3'),
        ],
    ],
];
