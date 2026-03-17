<?php

declare(strict_types=1);

namespace Modules\Network\Console;

use Illuminate\Console\Command;
use Modules\Contracts\Models\Contract;
use Modules\Network\Services\RadiusService;

/**
 * Sincronizza gli utenti RADIUS con i contratti attivi.
 * Utile dopo un'importazione massiva di contratti o migrazione.
 *
 * Usage:
 *   php artisan radius:sync-users
 *   php artisan radius:sync-users --tenant=1 --dry-run
 */
class SyncRadiusUsersCommand extends Command
{
    protected $signature = 'radius:sync-users
                            {--tenant= : ID tenant (ometti per tutti)}
                            {--dry-run : Mostra cosa verrebbe fatto senza apportare modifiche}';

    protected $description = 'Sincronizza utenti RADIUS con contratti attivi';

    public function handle(RadiusService $radius): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $dryRun   = (bool) $this->option('dry-run');

        $query = Contract::where('status', 'active')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->with('servicePlan');

        $total    = $query->count();
        $synced   = 0;
        $skipped  = 0;

        $this->info("Sincronizzazione RADIUS: {$total} contratti attivi" . ($dryRun ? ' [DRY-RUN]' : ''));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->lazyById(100)->each(function (Contract $contract) use ($radius, $dryRun, &$synced, &$skipped, $bar) {
            try {
                if (!$dryRun) {
                    $radius->provisionUser($contract);
                }
                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $this->newLine();
                $this->warn("Contratto #{$contract->id}: {$e->getMessage()}");
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
        $this->info("Completato: {$synced} sincronizzati, {$skipped} saltati.");

        return self::SUCCESS;
    }
}
