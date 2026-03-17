<?php

declare(strict_types=1);

namespace Modules\Provisioning\Console;

use Illuminate\Console\Command;
use Modules\Provisioning\Services\CarrierGateway;

/**
 * Verifica la connettività verso un carrier (Open Fiber / FiberCop).
 *
 * Usage:
 *   php artisan carrier:test-connection openfiber
 *   php artisan carrier:test-connection fibercop
 */
class TestCarrierConnectionCommand extends Command
{
    protected $signature = 'carrier:test-connection {carrier : openfiber|fibercop}';

    protected $description = 'Verifica connettività e autenticazione verso un carrier wholesale';

    public function handle(CarrierGateway $gateway): int
    {
        $carrier = strtolower($this->argument('carrier'));

        if (!in_array($carrier, ['openfiber', 'fibercop'])) {
            $this->error("Carrier non valido: {$carrier}. Usa 'openfiber' o 'fibercop'.");
            return self::FAILURE;
        }

        $this->info("Test connessione verso {$carrier}...");

        try {
            $result = $gateway->testConnection($carrier);

            if ($result['ok']) {
                $this->info("✓ Connessione OK — {$result['message']}");
                return self::SUCCESS;
            }

            $this->error("✗ Connessione fallita — {$result['message']}");
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Errore: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
