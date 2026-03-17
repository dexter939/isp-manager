<?php

namespace Modules\Contracts\WizardMobile\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class WizardMobileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/wizard_mobile.php', 'wizard_mobile');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
