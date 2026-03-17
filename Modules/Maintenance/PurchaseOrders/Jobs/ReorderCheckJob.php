<?php

namespace Modules\Maintenance\PurchaseOrders\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Maintenance\PurchaseOrders\Services\PurchaseOrderService;

class ReorderCheckJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PurchaseOrderService $service): void
    {
        $service->checkReorderAlerts();
    }
}
