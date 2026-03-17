<?php
namespace Modules\Maintenance\RouteOptimizer\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Maintenance\RouteOptimizer\Console\Commands\RoutesOptimizeCommand;
class RouteOptimizerServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../Routes/api.php');
        if ($this->app->runningInConsole()) {
            $this->commands([RoutesOptimizeCommand::class]);
        }
    }
    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../Config/route_optimizer.php', 'route_optimizer');
    }
}
