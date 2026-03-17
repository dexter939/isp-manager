<?php

declare(strict_types=1);

namespace Modules\Monitoring\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Monitoring\Console\CheckCpeCommand;
use Modules\Monitoring\Console\PollBtsCommand;
use Modules\Monitoring\Jobs\SnmpPollerJob;

class MonitoringServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'Monitoring';
    protected string $moduleNameLower = 'monitoring';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerConfig();
        $this->registerSchedule();

        if ($this->app->runningInConsole()) {
            $this->commands([
                PollBtsCommand::class,
                CheckCpeCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // SNMP polling BTS ogni 5 minuti
            $schedule->job(SnmpPollerJob::class, 'monitoring')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->onOneServer()
                ->name('snmp-poller');
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
