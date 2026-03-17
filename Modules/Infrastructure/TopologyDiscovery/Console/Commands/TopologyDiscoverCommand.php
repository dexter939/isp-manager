<?php

declare(strict_types=1);

namespace Modules\Infrastructure\TopologyDiscovery\Console\Commands;

use Illuminate\Console\Command;
use Modules\Infrastructure\TopologyDiscovery\Jobs\TopologyDiscoveryJob;

class TopologyDiscoverCommand extends Command
{
    protected $signature = 'topology:discover {--queue : Dispatch as queued job (default: synchronous)}';

    protected $description = 'Run LLDP/SNMP topology discovery scan across all tenant devices';

    public function handle(): int
    {
        if ($this->option('queue')) {
            TopologyDiscoveryJob::dispatch();
            $this->info('TopologyDiscoveryJob dispatched to queue.');
        } else {
            $this->info('Running topology discovery...');
            TopologyDiscoveryJob::dispatchSync();
            $this->info('Topology discovery completed.');
        }

        return self::SUCCESS;
    }
}
