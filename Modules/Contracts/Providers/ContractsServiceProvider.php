<?php

declare(strict_types=1);

namespace Modules\Contracts\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Contracts\Services\ContractService;
use Modules\Contracts\Services\CustomerService;
use Modules\Contracts\Services\DocumentStorageService;
use Modules\Contracts\Services\FEAService;
use Modules\Contracts\Services\NotificationService;
use Modules\Contracts\Services\PdfGeneratorService;

class ContractsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Contracts';
    protected string $moduleNameLower = 'contracts';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->registerConfig();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerServices();
    }

    protected function registerServices(): void
    {
        $this->app->singleton(CustomerService::class);
        $this->app->singleton(DocumentStorageService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(PdfGeneratorService::class);

        $this->app->singleton(ContractService::class, function ($app) {
            return new ContractService($app->make(CustomerService::class));
        });

        $this->app->singleton(FEAService::class, function ($app) {
            return new FEAService(
                $app->make(PdfGeneratorService::class),
                $app->make(DocumentStorageService::class),
                $app->make(NotificationService::class),
            );
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
