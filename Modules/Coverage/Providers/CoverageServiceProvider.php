<?php

declare(strict_types=1);

namespace Modules\Coverage\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Coverage\Console\Commands\CoverageStatsCommand;
use Modules\Coverage\Console\Commands\ImportCoverageCommand;
use Modules\Coverage\Elevation\Providers\ElevationServiceProvider;
use Modules\Coverage\Services\AddressNormalizer;
use Modules\Coverage\Services\FeasibilityService;

class CoverageServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Coverage';
    protected string $moduleNameLower = 'coverage';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerConfig();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCoverageCommand::class,
                CoverageStatsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(ElevationServiceProvider::class);

        $this->app->singleton(AddressNormalizer::class);

        $this->app->singleton(FeasibilityService::class, function ($app) {
            return new FeasibilityService($app->make(AddressNormalizer::class));
        });
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }
}
