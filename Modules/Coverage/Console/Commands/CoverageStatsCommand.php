<?php

declare(strict_types=1);

namespace Modules\Coverage\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mostra statistiche sul database di copertura locale.
 *
 * Usage:
 *   php artisan coverage:stats
 *   php artisan coverage:stats --carrier=fibercop
 */
class CoverageStatsCommand extends Command
{
    protected $signature = 'coverage:stats
                            {--carrier= : fibercop|openfiber (ometti per entrambi)}';

    protected $description = 'Mostra statistiche database copertura locale (FiberCop + Open Fiber)';

    public function handle(): int
    {
        $carrier = $this->option('carrier');

        $this->info('=== Coverage Database Statistics ===');
        $this->newLine();

        if (!$carrier || $carrier === 'fibercop') {
            $this->showCarrierStats('fibercop', 'coverage_fibercop');
        }

        if (!$carrier || $carrier === 'openfiber') {
            $this->showCarrierStats('openfiber', 'coverage_openfiber');
        }

        $this->showAddressRegistry();
        $this->showImportLogs($carrier);

        return self::SUCCESS;
    }

    private function showCarrierStats(string $carrier, string $table): void
    {
        $this->line("<comment>[{$carrier}]</comment>");

        if (!$this->tableExists($table)) {
            $this->warn("  Tabella {$table} non trovata.");
            $this->newLine();
            return;
        }

        $total = DB::table($table)->count();

        $byTech = DB::table($table)
            ->selectRaw('technology, COUNT(*) as count')
            ->groupBy('technology')
            ->orderByDesc('count')
            ->get();

        $this->table(
            ['Tecnologia', 'Record'],
            $byTech->map(fn($r) => [$r->technology, number_format($r->count)])->toArray()
        );

        $this->line("  Totale: <info>" . number_format($total) . "</info> record");

        $lastUpdate = DB::table($table)->max('updated_at');
        if ($lastUpdate) {
            $this->line("  Ultimo aggiornamento: <info>{$lastUpdate}</info>");
        }

        $this->newLine();
    }

    private function showAddressRegistry(): void
    {
        $this->line('<comment>[address_registry]</comment>');

        if (!$this->tableExists('address_registry')) {
            $this->warn('  Tabella address_registry non trovata.');
            $this->newLine();
            return;
        }

        $total    = DB::table('address_registry')->count();
        $withGeo  = DB::table('address_registry')->whereNotNull('lat')->count();

        $this->line("  Indirizzi totali:       <info>" . number_format($total) . "</info>");
        $this->line("  Con coordinate GPS:     <info>" . number_format($withGeo) . "</info>");
        $this->newLine();
    }

    private function showImportLogs(?string $carrier): void
    {
        $this->line('<comment>[import history (last 5)]</comment>');

        if (!$this->tableExists('coverage_import_logs')) {
            $this->warn('  Tabella coverage_import_logs non trovata.');
            return;
        }

        $logs = DB::table('coverage_import_logs')
            ->when($carrier, fn($q) => $q->where('carrier', $carrier))
            ->orderByDesc('started_at')
            ->limit(5)
            ->get(['carrier', 'status', 'rows_inserted', 'rows_updated', 'duration_seconds', 'started_at']);

        if ($logs->isEmpty()) {
            $this->line('  Nessun import registrato.');
            return;
        }

        $this->table(
            ['Carrier', 'Status', 'Inseriti', 'Aggiornati', 'Durata (s)', 'Avviato'],
            $logs->map(fn($r) => [
                $r->carrier,
                $r->status,
                number_format($r->rows_inserted),
                number_format($r->rows_updated),
                $r->duration_seconds ?? '-',
                $r->started_at,
            ])->toArray()
        );
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
