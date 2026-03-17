<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Services\ApiQuotaManager;

class CoreServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Core';
    protected string $moduleNameLower = 'core';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerConfig();
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Singleton ApiQuotaManager
        $this->app->singleton(ApiQuotaManager::class, function ($app) {
            return new ApiQuotaManager($app['redis'], $app['config']);
        });
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function registerRoutes(): void
    {
        // Routes registered via RouteServiceProvider
    }
}
