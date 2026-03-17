<?php
namespace Modules\Maintenance\OnCall\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class OnCallServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../Routes/api.php');
    }
}
