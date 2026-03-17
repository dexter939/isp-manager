<?php

declare(strict_types=1);

namespace Modules\Provisioning\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Provisioning\Console\RetryFailedOrdersCommand;
use Modules\Provisioning\Console\TestCarrierConnectionCommand;

class ProvisioningServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Provisioning';
    protected string $moduleNameLower = 'provisioning';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerConfig();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestCarrierConnectionCommand::class,
                RetryFailedOrdersCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }
}
