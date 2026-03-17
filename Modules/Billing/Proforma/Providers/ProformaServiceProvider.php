<?php
namespace Modules\Billing\Proforma\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class ProformaServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../Routes/api.php');
    }
    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../Config/proforma.php', 'proforma');
    }
}
