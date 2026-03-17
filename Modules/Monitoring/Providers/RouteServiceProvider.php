<?php

declare(strict_types=1);

namespace Modules\Monitoring\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Monitoring';
    protected string $moduleNamespace = 'Modules\Monitoring\Http\Controllers';

    public function map(): void
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path($this->moduleName, '/Routes/api.php'));
    }
}
