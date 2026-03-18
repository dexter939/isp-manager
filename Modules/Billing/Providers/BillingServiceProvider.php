<?php

declare(strict_types=1);

namespace Modules\Billing\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Billing\Console\GenerateMonthlyInvoicesCommand;
use Modules\Billing\Console\GenerateSepaCommand;
use Modules\Billing\Console\ImportSepaStatusCommand;
use Modules\Billing\Jobs\BillingCycleJob;
use Modules\Billing\Jobs\DunningJob;
use Modules\Billing\Jobs\PrepaidBillingJob;
use Modules\Billing\Bundles\Providers\BundlesServiceProvider;
use Modules\Billing\Cdr\Providers\CdrServiceProvider;
use Modules\Billing\CustomerBalance\Providers\CustomerBalanceServiceProvider;
use Modules\Billing\DunningManager\Providers\DunningManagerServiceProvider;
use Modules\Billing\Models\Invoice;
use Modules\Billing\OnlinePayments\Providers\OnlinePaymentsServiceProvider;
use Modules\Billing\PaymentMatching\Providers\PaymentMatchingServiceProvider;
use Modules\Billing\Policies\InvoicePolicy;
use Modules\Billing\PosteItaliane\Providers\PosteItalianeServiceProvider;
use Modules\Billing\Proforma\Providers\ProformaServiceProvider;
use Modules\Billing\Sdi\Providers\SdiServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'Billing';
    protected string $moduleNameLower = 'billing';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerConfig();
        $this->registerPolicies();
        $this->registerSchedule();

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateMonthlyInvoicesCommand::class,
                GenerateSepaCommand::class,
                ImportSepaStatusCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(BundlesServiceProvider::class);
        $this->app->register(CdrServiceProvider::class);
        $this->app->register(CustomerBalanceServiceProvider::class);
        $this->app->register(DunningManagerServiceProvider::class);
        $this->app->register(OnlinePaymentsServiceProvider::class);
        $this->app->register(PaymentMatchingServiceProvider::class);
        $this->app->register(PosteItalianeServiceProvider::class);
        $this->app->register(ProformaServiceProvider::class);
        $this->app->register(SdiServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }

        $prepaidConfigPath = module_path($this->moduleName, 'Config/prepaid.php');
        if (file_exists($prepaidConfigPath)) {
            $this->mergeConfigFrom($prepaidConfigPath, 'prepaid');
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }

    protected function registerSchedule(): void
    {
        // Registra i job schedulati quando l'applicazione è pronta
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Ciclo di fatturazione: ogni giorno alle 00:30
            $schedule->job(BillingCycleJob::class, 'billing')
                ->dailyAt('00:30')
                ->withoutOverlapping()
                ->onOneServer()
                ->name('billing-cycle');

            // Dunning: ogni ora
            $schedule->job(DunningJob::class, 'billing')
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer()
                ->name('dunning-processor');

            // Prepaid billing: ogni giorno alle 02:00
            $schedule->job(PrepaidBillingJob::class, 'billing')
                ->dailyAt(config('prepaid.billing_job_time', '02:00'))
                ->withoutOverlapping()
                ->onOneServer()
                ->name('prepaid-billing');
        });
    }
}
