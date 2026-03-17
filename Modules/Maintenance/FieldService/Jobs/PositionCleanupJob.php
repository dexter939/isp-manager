<?php
namespace Modules\Maintenance\FieldService\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Maintenance\FieldService\Services\TechnicianTracker;
class PositionCleanupJob implements ShouldQueue, ShouldBeUnique {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 1;
    public function handle(TechnicianTracker $tracker): void {
        $deleted = $tracker->cleanup();
        logger()->info("FieldService position cleanup: deleted {$deleted} records.");
    }
}
