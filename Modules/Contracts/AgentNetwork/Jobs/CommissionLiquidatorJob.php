<?php

namespace Modules\Contracts\AgentNetwork\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Contracts\AgentNetwork\Services\CommissionLiquidationService;

class CommissionLiquidatorJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(CommissionLiquidationService $service): void
    {
        $result = $service->generateLiquidation(now()->startOfMonth());
        logger()->info('Commission liquidation completed', $result);
    }
}
