<?php
namespace Modules\Maintenance\OnCall\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Maintenance\OnCall\Services\OnCallService;
class EscalateAlertJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable;
    public function __construct(private readonly string $dispatchId) {}
    public function handle(OnCallService $service): void {
        $service->escalate($this->dispatchId);
    }
}
