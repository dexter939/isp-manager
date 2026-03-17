<?php

declare(strict_types=1);

namespace Modules\Network\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Billing\Events\PaymentReceived;
use Modules\Contracts\Events\ContractSigned;
use Modules\Contracts\Events\ContractStatusChanged;
use Modules\Network\Console\ExportRetentionCommand;
use Modules\Network\Console\SyncRadiusUsersCommand;
use Modules\Network\Jobs\SyncAgcomListJob;
use Modules\Network\Listeners\DeprovisionRadiusUserListener;
use Modules\Network\Listeners\ProvisionRadiusUserListener;
use Modules\Network\Listeners\RestoreRadiusAccessListener;
use Modules\Network\Listeners\SuspendRadiusUserListener;
use Modules\Network\Services\DnsFilter\DnsFilterResolverInterface;
use Modules\Network\Services\DnsFilter\LocalBindResolver;
use Modules\Network\Services\DnsFilter\WhaleboneResolver;

class NetworkServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'Network';
    protected string $moduleNameLower = 'network';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerConfig();
        $this->registerEventListeners();
        $this->registerSchedule();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportRetentionCommand::class,
                SyncRadiusUsersCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerDnsFilterResolver();
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }

        $pcConfigPath = module_path($this->moduleName, 'Config/parental_control.php');
        if (file_exists($pcConfigPath)) {
            $this->mergeConfigFrom($pcConfigPath, 'parental_control');
        }
    }

    protected function registerDnsFilterResolver(): void
    {
        $this->app->bind(DnsFilterResolverInterface::class, function ($app) {
            return match(config('parental_control.resolver_driver', 'whalebone')) {
                'local_bind' => new LocalBindResolver(),
                default      => new WhaleboneResolver(),
            };
        });
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(SyncAgcomListJob::class)->dailyAt('03:00');
        });
    }

    protected function registerEventListeners(): void
    {
        // ContractSigned → provisiona utente RADIUS
        Event::listen(ContractSigned::class, ProvisionRadiusUserListener::class);

        // ContractStatusChanged → Suspended → walled garden CoA
        Event::listen(ContractStatusChanged::class, SuspendRadiusUserListener::class);

        // ContractStatusChanged → Terminated → deprovision + Disconnect
        Event::listen(ContractStatusChanged::class, DeprovisionRadiusUserListener::class);

        // PaymentReceived → ripristina accesso CoA
        Event::listen(PaymentReceived::class, RestoreRadiusAccessListener::class);
    }
}
