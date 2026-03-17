<?php

declare(strict_types=1);

namespace Modules\Provisioning\Console;

use Illuminate\Console\Command;
use Modules\Provisioning\Jobs\SubmitCarrierOrderJob;
use Modules\Provisioning\Models\ProvisioningOrder;

/**
 * Reinvia gli ordini di provisioning falliti verso il carrier.
 *
 * Usage:
 *   php artisan orders:retry-failed
 *   php artisan orders:retry-failed --tenant=1
 *   php artisan orders:retry-failed --max=50
 */
class RetryFailedOrdersCommand extends Command
{
    protected $signature = 'orders:retry-failed
                            {--tenant= : ID tenant (ometti per tutti)}
                            {--max=100 : Numero massimo di ordini da ritentare}';

    protected $description = 'Reinvia ordini di provisioning in stato failed verso il carrier';

    public function handle(): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $max      = (int) $this->option('max');

        $orders = ProvisioningOrder::where('status', 'failed')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->limit($max)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Nessun ordine fallito da ritentare.');
            return self::SUCCESS;
        }

        $this->info("Ritento {$orders->count()} ordini falliti...");

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            SubmitCarrierOrderJob::dispatch($order);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$orders->count()} job inviati in coda.");

        return self::SUCCESS;
    }
}
