<?php
namespace Modules\Billing\Cdr\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Cdr\Services\CdrBillingService;
class CdrBillingJob implements ShouldQueue, ShouldBeUnique {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 1;
    public function handle(CdrBillingService $service): void {
        $result = $service->runMonthlyBilling(now()->subMonth()->startOfMonth());
        logger()->info('CDR billing completed', $result);
    }
}
