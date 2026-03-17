<?php

declare(strict_types=1);

namespace Modules\Monitoring\Console;

use Illuminate\Console\Command;
use Modules\Monitoring\Jobs\SnmpPollerJob;

/**
 * Trigger manuale del polling SNMP BTS (normalmente schedulato ogni 5 minuti).
 *
 * Usage:
 *   php artisan monitoring:poll-bts
 *   php artisan monitoring:poll-bts --sync   # esegui inline senza queue
 */
class PollBtsCommand extends Command
{
    protected $signature = 'monitoring:poll-bts
                            {--sync : Esegui inline invece di fare dispatch sulla queue}';

    protected $description = 'Esegui polling SNMP su tutte le BTS attive';

    public function handle(): int
    {
        if ($this->option('sync')) {
            $this->info('Polling SNMP BTS in corso (sync)...');
            (new SnmpPollerJob())->handle();
            $this->info('Completato.');
        } else {
            SnmpPollerJob::dispatch();
            $this->info('Job SnmpPollerJob inviato in coda.');
        }

        return self::SUCCESS;
    }
}
