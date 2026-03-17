<?php

declare(strict_types=1);

namespace Modules\Coverage\Console\Commands;

use Illuminate\Console\Command;
use Modules\Coverage\Jobs\ImportCoverageJob;

/**
 * php artisan coverage:import fibercop --file=/path/to/netmap.csv
 * php artisan coverage:import openfiber --file=/path/to/coverage.csv
 */
class ImportCoverageCommand extends Command
{
    protected $signature = 'coverage:import
                            {carrier : fibercop o openfiber}
                            {--file= : Path del file CSV (obbligatorio se non --sftp)}
                            {--disk=local : Disco storage (local, s3)}
                            {--sync : Esegui in modo sincrono (no queue)}';

    protected $description = 'Importa file copertura CSV FiberCop NetMap o Open Fiber';

    public function handle(): int
    {
        $carrier = $this->argument('carrier');
        $file    = $this->option('file');
        $disk    = $this->option('disk');

        if (!in_array($carrier, ['fibercop', 'openfiber'], true)) {
            $this->error('Carrier non valido. Usa: fibercop | openfiber');
            return self::FAILURE;
        }

        if (!$file) {
            $this->error('Specifica --file=/path/al/file.csv');
            return self::FAILURE;
        }

        $this->info("Accodando import {$carrier} da: {$file}");

        $job = new ImportCoverageJob($carrier, $file, $disk);

        if ($this->option('sync')) {
            $this->info('Esecuzione sincrona...');
            dispatch_sync($job);
            $this->info('Import completato.');
        } else {
            dispatch($job)->onQueue('imports');
            $this->info('Job accodato. Monitor: php artisan horizon');
        }

        return self::SUCCESS;
    }
}
