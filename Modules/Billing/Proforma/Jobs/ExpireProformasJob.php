<?php
namespace Modules\Billing\Proforma\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Billing\Proforma\Services\ProformaService;
class ExpireProformasJob implements ShouldQueue, ShouldBeUnique {
    use Dispatchable, InteractsWithQueue, Queueable;
    public int $tries = 1;
    public function handle(ProformaService $service): void {
        $expired = $service->expireUnpaidProformas();
        logger()->info("ExpireProformasJob: expired {$expired} proformas");
    }
}
