<?php

declare(strict_types=1);

namespace Modules\Billing\Console;

use Illuminate\Console\Command;
use Modules\Billing\Services\SddService;

/**
 * Genera il file SEPA SDD pain.008 per addebiti del giorno.
 *
 * Usage:
 *   php artisan billing:generate-sepa
 *   php artisan billing:generate-sepa --date=2024-03-15
 *   php artisan billing:generate-sepa --date=2024-03-15 --tenant=1
 */
class GenerateSepaCommand extends Command
{
    protected $signature = 'billing:generate-sepa
                            {--date= : Data addebito (Y-m-d, default: oggi)}
                            {--tenant= : ID tenant (ometti per tutti)}';

    protected $description = 'Genera file SEPA SDD pain.008 per gli addebiti del giorno';

    public function handle(SddService $sdd): int
    {
        $date     = $this->option('date') ?? now()->toDateString();
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        $this->info("Generazione SEPA SDD per data: {$date}");

        try {
            $file = $sdd->generateFile(
                collectionDate: $date,
                tenantId:       $tenantId,
            );

            $this->info("File SEPA generato: ID #{$file->id} ({$file->filename}) — {$file->transaction_count} transazioni, totale {$file->total_amount} EUR");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Generazione SEPA fallita: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
