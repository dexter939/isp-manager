<?php
namespace Modules\Network\FairUsage\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Network\FairUsage\Services\TrafficAccountingService;
class FupResetJob implements ShouldQueue, ShouldBeUnique {
    use Dispatchable, InteractsWithQueue, Queueable;
    public int $tries = 1;
    public function handle(TrafficAccountingService $service): void {
        $count = $service->resetMonthlyCounters();
        logger()->info("FupResetJob: reset {$count} traffic usage counters");
    }
}
