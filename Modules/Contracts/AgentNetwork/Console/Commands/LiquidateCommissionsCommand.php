<?php

namespace Modules\Contracts\AgentNetwork\Console\Commands;

use Illuminate\Console\Command;
use Modules\Contracts\AgentNetwork\Jobs\CommissionLiquidatorJob;

class LiquidateCommissionsCommand extends Command
{
    protected $signature   = 'agents:liquidate {--month= : Month to liquidate (YYYY-MM)}';
    protected $description = 'Generate monthly commission liquidations for all agents';

    public function handle(): int
    {
        CommissionLiquidatorJob::dispatch();
        $this->info('Commission liquidation job dispatched.');
        return self::SUCCESS;
    }
}
