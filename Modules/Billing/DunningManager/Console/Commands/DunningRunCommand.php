<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Console\Commands;

use Illuminate\Console\Command;
use Modules\Billing\DunningManager\Jobs\DunningSchedulerJob;

class DunningRunCommand extends Command
{
    protected $signature   = 'dunning:run {--sync : Run synchronously instead of dispatching to queue}';
    protected $description = 'Run the dunning scheduler: open new cases for overdue invoices and process due steps.';

    public function handle(): int
    {
        $this->info('Starting dunning run...');

        if ($this->option('sync')) {
            app(DunningSchedulerJob::class)->handle(
                app(\Modules\Billing\DunningManager\Services\DunningManager::class)
            );
            $this->info('Dunning run completed synchronously.');
        } else {
            DunningSchedulerJob::dispatch();
            $this->info('DunningSchedulerJob dispatched to queue.');
        }

        return self::SUCCESS;
    }
}
