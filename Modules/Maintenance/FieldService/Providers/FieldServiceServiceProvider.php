<?php
namespace Modules\Maintenance\FieldService\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class FieldServiceServiceProvider extends ServiceProvider {
    public function register(): void { $this->mergeConfigFrom(__DIR__ . '/../Config/field_service.php', 'field_service'); }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        Route::prefix('api')->middleware('api')->group(__DIR__ . '/../Routes/api.php');
    }
}
