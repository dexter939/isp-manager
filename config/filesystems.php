<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => true,
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        'minio' => [
            'driver'                  => 's3',
            'key'                     => env('MINIO_KEY'),
            'secret'                  => env('MINIO_SECRET'),
            'region'                  => env('MINIO_REGION', 'us-east-1'),
            'bucket'                  => env('MINIO_BUCKET', 'ispmanager'),
            'url'                     => env('MINIO_URL'),
            'endpoint'                => env('MINIO_ENDPOINT'),
            'use_path_style_endpoint' => env('MINIO_PATH_STYLE', true),
            'throw'                   => true,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
