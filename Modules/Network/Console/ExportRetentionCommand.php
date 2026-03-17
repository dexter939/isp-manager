<?php

declare(strict_types=1);

namespace Modules\Network\Console;

use Illuminate\Console\Command;
use Modules\Network\Services\DataRetentionService;

/**
 * Esporta log sessioni RADIUS per Polizia Postale.
 *
 * Accesso riservato — eseguire solo su richiesta autorità competente
 * con autorizzazione scritta dell'ISP manager.
 *
 * Usage:
 *   php artisan radius:export-retention --from=2024-01-01 --to=2024-12-31
 *   php artisan radius:export-retention --from=2024-01-01 --to=2024-12-31 --tenant=1 --output=/tmp/export.csv
 */
class ExportRetentionCommand extends Command
{
    protected $signature = 'radius:export-retention
                            {--from= : Data inizio (Y-m-d)}
                            {--to= : Data fine (Y-m-d)}
                            {--tenant= : ID tenant (ometti per tutti)}
                            {--output= : Percorso file CSV (default: storage/retention/YYYY-MM-DD_HH-mm-ss.csv)}';

    protected $description = '[GDPR/Pisanu] Esporta log sessioni RADIUS per autorità giudiziaria';

    public function handle(DataRetentionService $service): int
    {
        $from   = $this->option('from');
        $to     = $this->option('to');

        if (!$from || !$to) {
            $this->error('Le opzioni --from e --to sono obbligatorie.');
            return self::FAILURE;
        }

        $tenantId  = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $outputPath = $this->option('output') ?? storage_path('retention/' . now()->format('Y-m-d_H-i-s') . '.csv');

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $this->info("Esportazione sessioni RADIUS: {$from} → {$to}");
        $this->warn('ATTENZIONE: Questo export contiene dati soggetti al Decreto Pisanu. Conservare in modo sicuro.');

        if (!$this->confirm('Confermi l\'esportazione?')) {
            return self::SUCCESS;
        }

        try {
            $count = $service->exportToCsv(
                fromDate:   $from,
                toDate:     $to,
                outputPath: $outputPath,
                tenantId:   $tenantId,
            );

            $this->info("Export completato: {$count} sessioni → {$outputPath}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Export fallito: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
