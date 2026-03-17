<?php
namespace Modules\Infrastructure\NetworkSites\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class NetworkSitesServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../Routes/api.php');
    }
}
