<?php
namespace Modules\Billing\Cdr\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class CdrServiceProvider extends ServiceProvider
{
    public function register(): void { $this->mergeConfigFrom(__DIR__ . '/../Config/cdr.php', 'cdr'); }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__ . '/../Routes/api.php');
    }
}
