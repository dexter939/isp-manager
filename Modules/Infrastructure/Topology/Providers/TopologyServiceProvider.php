<?php
namespace Modules\Infrastructure\Topology\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class TopologyServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../Routes/api.php');
    }
}
