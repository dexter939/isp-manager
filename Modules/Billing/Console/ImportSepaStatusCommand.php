<?php

declare(strict_types=1);

namespace Modules\Billing\Console;

use Illuminate\Console\Command;
use Modules\Billing\Services\SddService;

/**
 * Importa il file di stato R-transaction SEPA dalla banca.
 * Processa i codici di ritorno: AC04, AM04, MS02, MD01, MD06.
 *
 * Usage:
 *   php artisan billing:import-sepa-status /path/to/pain.002.xml
 */
class ImportSepaStatusCommand extends Command
{
    protected $signature = 'billing:import-sepa-status {file : Percorso file pain.002 XML}';

    protected $description = 'Importa stato R-transactions SEPA SDD (pain.002)';

    public function handle(SddService $sdd): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Importazione SEPA status da: {$filePath}");

        try {
            $xml    = file_get_contents($filePath);
            $result = $sdd->processReturnFile($xml);

            $this->info("Processati: {$result['processed']} R-transactions");

            if ($result['failed'] > 0) {
                $this->warn("Errori: {$result['failed']} transazioni non processate");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Import fallito: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
