<?php
namespace Modules\Infrastructure\TopologyDiscovery\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Infrastructure\TopologyDiscovery\Console\Commands\TopologyDiscoverCommand;
class TopologyDiscoveryServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../Routes/api.php');
        if ($this->app->runningInConsole()) {
            $this->commands([TopologyDiscoverCommand::class]);
        }
    }
}
