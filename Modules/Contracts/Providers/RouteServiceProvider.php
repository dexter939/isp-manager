<?php

declare(strict_types=1);

namespace Modules\Contracts\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Contracts';
    protected string $moduleNamespace = 'Modules\Contracts\Http\Controllers';

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(module_path($this->moduleName, '/Routes/api.php'));
    }

    protected function mapWebRoutes(): void
    {
        $webFile = module_path($this->moduleName, '/Routes/web.php');
        if (file_exists($webFile)) {
            Route::middleware('web')
                ->group($webFile);
        }
    }
}
