<?php
namespace Modules\Infrastructure\TopologyDiscovery\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Infrastructure\TopologyDiscovery\Services\TopologyDiscoveryService;
class TopologyDiscoveryJob implements ShouldQueue, ShouldBeUnique {
    use Dispatchable, InteractsWithQueue, Queueable;
    public int $tries = 1;
    public function handle(TopologyDiscoveryService $service): void {
        $run = $service->runDiscovery();
        logger()->info("TopologyDiscovery completed: {$run->links_discovered} links found, {$run->devices_scanned} devices scanned");
    }
}
