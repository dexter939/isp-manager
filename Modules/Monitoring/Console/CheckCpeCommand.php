<?php

declare(strict_types=1);

namespace Modules\Monitoring\Console;

use Illuminate\Console\Command;
use Modules\Contracts\Models\Contract;
use Modules\Monitoring\Services\LineTestingService;
use Modules\Monitoring\Services\TR069Service;

/**
 * Esegue un check completo su un CPE: TR-069 + Line Test.
 * Utile per diagnosi manuale da CLI (call center / NOC).
 *
 * Usage:
 *   php artisan monitoring:check-cpe 1234
 *   php artisan monitoring:check-cpe 1234 --linetest
 *   php artisan monitoring:check-cpe 1234 --tr069-only
 */
class CheckCpeCommand extends Command
{
    protected $signature = 'monitoring:check-cpe
                            {contract_id : ID del contratto}
                            {--linetest : Esegui anche il line test carrier (consuma quota)}
                            {--tr069-only : Solo parametri TR-069, nessun line test}';

    protected $description = 'Controlla stato CPE (TR-069 + line test) per un contratto';

    public function handle(TR069Service $tr069, LineTestingService $lineTesting): int
    {
        $contractId = (int) $this->argument('contract_id');

        $contract = Contract::with(['customer', 'servicePlan'])->find($contractId);

        if (!$contract) {
            $this->error("Contratto #{$contractId} non trovato.");
            return self::FAILURE;
        }

        $this->info("=== CPE Check — Contratto #{$contract->id} ===");
        $this->line("Cliente:   <info>{$contract->customer?->ragione_sociale}</info>");
        $this->line("Piano:     <info>{$contract->servicePlan?->name}</info>");
        $this->line("Carrier:   <info>{$contract->carrier}</info>");
        $this->line("Codice UI: <info>{$contract->codice_ui}</info>");
        $this->newLine();

        // ── TR-069 ────────────────────────────────────────────────────────────
        if (!$this->option('linetest')) {
            $this->line('<comment>[TR-069 Parametri CPE]</comment>');

            try {
                $params = $tr069->getParameters($contract->cpeDevice ?? (object)['serial_number' => $contract->codice_ui], [
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Uptime',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Stats.TotalBytesSent',
                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Stats.TotalBytesReceived',
                ]);

                foreach ($params as $name => $value) {
                    $shortName = last(explode('.', $name));
                    $this->line("  {$shortName}: <info>{$value}</info>");
                }
            } catch (\Throwable $e) {
                $this->warn("TR-069 non disponibile: {$e->getMessage()}");
            }

            $this->newLine();
        }

        // ── Line Test ─────────────────────────────────────────────────────────
        if (!$this->option('tr069-only')) {
            $this->line('<comment>[Line Test]</comment>');

            try {
                $result = match ($contract->carrier) {
                    'openfiber' => $lineTesting->testOpenFiber($contract, 'cli'),
                    'fibercop'  => $lineTesting->testFiberCop($contract, 'cli'),
                    default     => throw new \InvalidArgumentException("Carrier {$contract->carrier} non supportato per line test"),
                };

                $this->table(
                    ['Campo', 'Valore'],
                    [
                        ['Risultato',    $result->result],
                        ['Codice Errore', $result->error_code ?? 'N/A'],
                        ['Attenuazione', $result->attenuation ? "{$result->attenuation} dBm" : 'N/A'],
                        ['Dist. ottica', $result->optical_distance ? "{$result->optical_distance} m" : 'N/A'],
                        ['Da cache',     $result->from_cache ? 'Sì' : 'No'],
                        ['Testato il',   $result->tested_at],
                    ]
                );
            } catch (\Throwable $e) {
                $this->warn("Line test non disponibile: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
