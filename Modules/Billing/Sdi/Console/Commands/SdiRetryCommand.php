<?php

namespace Modules\Billing\Sdi\Console\Commands;

use Illuminate\Console\Command;
use Modules\Billing\Sdi\Jobs\SdiRetryJob;

class SdiRetryCommand extends Command
{
    protected $signature   = 'sdi:retry';
    protected $description = 'Retry failed SDI transmissions';

    public function handle(): int
    {
        SdiRetryJob::dispatch();
        $this->info('SDI retry job dispatched.');
        return self::SUCCESS;
    }
}
