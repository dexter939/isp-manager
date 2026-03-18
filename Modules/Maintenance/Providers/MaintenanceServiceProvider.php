<?php

declare(strict_types=1);

namespace Modules\Maintenance\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Maintenance\Dispatcher\Providers\DispatcherServiceProvider;
use Modules\Maintenance\FieldService\Providers\FieldServiceServiceProvider;
use Modules\Maintenance\InventoryRma\Providers\InventoryRmaServiceProvider;
use Modules\Maintenance\Listeners\CreateTicketFromAlertListener;
use Modules\Maintenance\Models\InventoryItem;
use Modules\Maintenance\Models\TroubleTicket;
use Modules\Maintenance\OnCall\Providers\OnCallServiceProvider;
use Modules\Maintenance\Policies\InventoryPolicy;
use Modules\Maintenance\Policies\TicketPolicy;
use Modules\Maintenance\PurchaseOrders\Providers\PurchaseOrdersServiceProvider;
use Modules\Maintenance\RouteOptimizer\Providers\RouteOptimizerServiceProvider;
use Modules\Monitoring\Events\NetworkAlertCreated;

class MaintenanceServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'Maintenance';
    protected string $moduleNameLower = 'maintenance';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerConfig();
        $this->registerPolicies();
        $this->registerEventListeners();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(DispatcherServiceProvider::class);
        $this->app->register(FieldServiceServiceProvider::class);
        $this->app->register(InventoryRmaServiceProvider::class);
        $this->app->register(OnCallServiceProvider::class);
        $this->app->register(PurchaseOrdersServiceProvider::class);
        $this->app->register(RouteOptimizerServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(TroubleTicket::class, TicketPolicy::class);
        Gate::policy(InventoryItem::class, InventoryPolicy::class);
    }

    protected function registerEventListeners(): void
    {
        // Alert critico dal Monitoring → apri ticket assurance automatico
        Event::listen(NetworkAlertCreated::class, CreateTicketFromAlertListener::class);
    }
}
