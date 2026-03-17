<?php

namespace Modules\Billing\OnlinePayments\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class OnlinePaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/online_payments.php', 'online_payments');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
