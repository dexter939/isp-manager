<?php

declare(strict_types=1);

namespace Modules\Billing\Console;

use Illuminate\Console\Command;
use Modules\Billing\Services\InvoiceService;
use Modules\Contracts\Models\Contract;

/**
 * Genera le fatture mensili per i contratti attivi.
 * Normalmente eseguito dal BillingCycleJob il primo del mese alle 00:30.
 *
 * Usage:
 *   php artisan billing:generate-monthly
 *   php artisan billing:generate-monthly --month=2024-03
 *   php artisan billing:generate-monthly --month=2024-03 --dry-run
 *   php artisan billing:generate-monthly --tenant=1
 */
class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'billing:generate-monthly
                            {--month= : Mese di competenza (Y-m, default: mese corrente)}
                            {--tenant= : ID tenant (ometti per tutti)}
                            {--dry-run : Simula senza creare fatture}';

    protected $description = 'Genera fatture mensili per contratti attivi';

    public function handle(InvoiceService $invoiceService): int
    {
        $monthStr = $this->option('month') ?? now()->format('Y-m');
        $month    = \Carbon\Carbon::createFromFormat('Y-m', $monthStr)?->startOfMonth();

        if (!$month) {
            $this->error("Formato mese non valido: {$monthStr} (atteso: Y-m)");
            return self::FAILURE;
        }

        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $dryRun   = (bool) $this->option('dry-run');

        $this->info("Fatturazione mensile: {$month->format('Y-m')}" . ($dryRun ? ' [DRY-RUN]' : ''));

        $query = Contract::where('status', 'active')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['customer', 'servicePlan']);

        $total   = $query->count();
        $created = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->lazyById(100)->each(function (Contract $contract) use ($invoiceService, $month, $dryRun, &$created, &$skipped, $bar) {
            try {
                if (!$dryRun) {
                    $invoiceService->generateMonthly($contract, $month);
                }
                $created++;
            } catch (\Throwable $e) {
                $skipped++;
                $this->newLine();
                $this->warn("Contratto #{$contract->id}: {$e->getMessage()}");
            }
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
        $this->info("Completato: {$created} fatture create, {$skipped} saltate.");

        return self::SUCCESS;
    }
}
