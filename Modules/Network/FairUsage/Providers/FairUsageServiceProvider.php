<?php
namespace Modules\Network\FairUsage\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class FairUsageServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../Routes/api.php');
    }
    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../Config/fair_usage.php', 'fair_usage');
    }
}
