<?php

return [
    /*
    |--------------------------------------------------------------------------
    | nwidart/laravel-modules configuration
    |--------------------------------------------------------------------------
    */

    'namespace' => 'Modules',

    'stubs' => [
        'enabled' => false,
        'path'    => base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'),
        'files'   => [
            'routes/web'    => 'Routes/web.php',
            'routes/api'    => 'Routes/api.php',
            'views/index'   => 'Resources/views/index.blade.php',
            'views/master'  => 'Resources/views/layouts/master.blade.php',
            'scaffold/config'     => 'Config/config.php',
            'composer'      => 'composer.json',
            'assets/js/app' => 'Resources/assets/js/app.js',
            'assets/sass/app' => 'Resources/assets/sass/app.scss',
            'vite'          => 'vite.config.js',
            'package'       => 'package.json',
        ],
        'replacements' => [
            'routes/web'    => ['LOWER_NAME', 'STUDLY_NAME'],
            'routes/api'    => ['LOWER_NAME'],
            'vite'          => ['LOWER_NAME'],
            'json'          => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
            'views/index'   => ['LOWER_NAME'],
            'views/master'  => ['STUDLY_NAME'],
            'scaffold/config' => ['STUDLY_NAME'],
            'composer'      => ['LOWER_NAME', 'STUDLY_NAME', 'VENDOR', 'AUTHOR_NAME', 'AUTHOR_EMAIL', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
        ],
        'gitkeep' => true,
    ],

    'paths' => [
        'modules'  => base_path('Modules'),
        'assets'   => public_path('modules'),
        'migration' => base_path('Modules/{module}/Database/Migrations'),
        'app_folder' => 'app/',
        'generator' => [
            'config'     => ['path' => 'Config', 'generate' => true],
            'command'    => ['path' => 'Console', 'generate' => true],
            'migration'  => ['path' => 'Database/Migrations', 'generate' => true],
            'seeder'     => ['path' => 'Database/Seeders', 'generate' => true],
            'factory'    => ['path' => 'Database/factories', 'generate' => true],
            'model'      => ['path' => 'Models', 'generate' => true],
            'routes'     => ['path' => 'Routes', 'generate' => true],
            'controller' => ['path' => 'Http/Controllers', 'generate' => true],
            'filter'     => ['path' => 'Http/Middleware', 'generate' => true],
            'request'    => ['path' => 'Http/Requests', 'generate' => true],
            'provider'   => ['path' => 'Providers', 'generate' => true],
            'assets'     => ['path' => 'Resources/assets', 'generate' => true],
            'lang'       => ['path' => 'Resources/lang', 'generate' => false],
            'views'      => ['path' => 'Resources/views', 'generate' => false],
            'test'       => ['path' => 'Tests/Feature', 'generate' => true],
            'test-unit'  => ['path' => 'Tests/Unit', 'generate' => true],
            'repository' => ['path' => 'Repositories', 'generate' => false],
            'event'      => ['path' => 'Events', 'generate' => true],
            'listener'   => ['path' => 'Listeners', 'generate' => true],
            'policies'   => ['path' => 'Policies', 'generate' => true],
            'rules'      => ['path' => 'Rules', 'generate' => false],
            'jobs'       => ['path' => 'Jobs', 'generate' => true],
            'emails'     => ['path' => 'Emails', 'generate' => false],
            'notifications' => ['path' => 'Notifications', 'generate' => true],
            'resource'   => ['path' => 'Transformers', 'generate' => false],
            'component-view'  => ['path' => 'Resources/views/components', 'generate' => false],
            'component-class' => ['path' => 'View/Components', 'generate' => false],
        ],
    ],

    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],

    'composer' => [
        'vendor' => 'ispmanager',
        'author' => [
            'name'  => 'ISP Manager Team',
            'email' => 'dev@ispmanager.it',
        ],
        'composer-output' => false,
    ],

    'cache' => [
        'enabled'  => false,
        'driver'   => 'file',
        'key'      => 'laravel-modules',
        'lifetime' => 60,
    ],

    'register' => [
        'translations' => true,
        'files' => 'register',
    ],

    'activators' => [
        'file' => [
            'class'          => \Nwidart\Modules\Activators\FileActivator::class,
            'statuses-file'  => base_path('modules_statuses.json'),
            'cache-key'      => 'activator.installed',
            'cache-lifetime' => 604800,
        ],
    ],

    'activator' => 'file',
];
