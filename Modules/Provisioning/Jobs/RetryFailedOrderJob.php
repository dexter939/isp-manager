<?php

declare(strict_types=1);

namespace Modules\Provisioning\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Enums\OrderType;
use Modules\Provisioning\Models\CarrierOrder;

/**
 * Schedulato ogni 5 minuti: esegue retry sugli ordini falliti.
 * Processa solo ordini con next_retry_at <= now.
 */
class RetryFailedOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'carrier-orders';

    public function handle(): void
    {
        $orders = CarrierOrder::pendingRetry()->get();

        if ($orders->isEmpty()) {
            return;
        }

        Log::info("RetryFailedOrderJob: {$orders->count()} ordini da ritentare");

        foreach ($orders as $order) {
            $this->retryOrder($order);
        }
    }

    private function retryOrder(CarrierOrder $order): void
    {
        $job = match ($order->order_type) {
            OrderType::Activation   => new SendActivationOrderJob($order->id),
            OrderType::Deactivation => new SendDeactivationOrderJob($order->id),
            default => null,
        };

        if ($job) {
            dispatch($job)->onQueue('carrier-orders');
            Log::info("Retry #{$order->retry_count} per ordine #{$order->id}");
        }
    }
}
