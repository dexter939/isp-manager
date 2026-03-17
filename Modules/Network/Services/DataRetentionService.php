<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Modules\Network\Models\RadiusSession;

/**
 * Export log sessioni RADIUS per Polizia Postale (Decreto Pisanu / D.Lgs. 109/2008).
 *
 * Obbligo: conservazione 6 anni dal termine della sessione.
 * Output: CSV con i campi obbligatori per l'autorità richiedente.
 * Destinazione: file su disco (storage/retention/) o stream diretto (CLI).
 *
 * IMPORTANTE:
 * - NON esporre mai questi dati via API
 * - Accesso solo via comando artisan autenticato (operatore autorizzato)
 * - I file generati devono finire su storage immutabile (MinIO WORM)
 */
class DataRetentionService
{
    /** CSV headers richiesti dal modello GdF/Polizia Postale */
    private const CSV_HEADERS = [
        'username',
        'nas_ip',
        'framed_ip',
        'framed_ipv6',
        'acct_session_id',
        'acct_start',
        'acct_stop',
        'acct_session_time_seconds',
        'acct_input_octets',
        'acct_output_octets',
        'calling_station_id',
        'called_station_id',
        'acct_terminate_cause',
    ];

    /**
     * Esporta le sessioni RADIUS in un range di date su file CSV.
     *
     * @param  string $fromDate  Data inizio (Y-m-d)
     * @param  string $toDate    Data fine (Y-m-d)
     * @param  string $outputPath  Percorso file di output
     * @param  int|null $tenantId  Filtro tenant (null = tutti i tenant)
     * @return int Numero di sessioni esportate
     */
    public function exportToCsv(
        string $fromDate,
        string $toDate,
        string $outputPath,
        ?int $tenantId = null,
    ): int {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->endOfDay();

        $this->validateRetentionWindow($from, $to);

        $handle = fopen($outputPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException("Impossibile aprire il file di output: {$outputPath}");
        }

        try {
            fputcsv($handle, self::CSV_HEADERS, ';');

            $count = 0;

            $this->lazyQuery($from, $to, $tenantId)->each(function (RadiusSession $session) use ($handle, &$count) {
                fputcsv($handle, [
                    $session->username,
                    $session->nas_ip,
                    $session->framed_ip,
                    $session->framed_ipv6,
                    $session->acct_session_id,
                    $session->acct_start?->format('Y-m-d H:i:s'),
                    $session->acct_stop?->format('Y-m-d H:i:s'),
                    $session->acct_session_time,
                    $session->acct_input_octets,
                    $session->acct_output_octets,
                    $session->calling_station_id,
                    $session->called_station_id,
                    $session->acct_terminate_cause,
                ], ';');

                $count++;
            });
        } finally {
            fclose($handle);
        }

        Log::info("DataRetention: esportate {$count} sessioni ({$fromDate} → {$toDate}) in {$outputPath}");

        return $count;
    }

    /**
     * Elimina sessioni scadute (oltre 6 anni) — da eseguire dopo archiviazione su MinIO WORM.
     *
     * @return int Numero di sessioni eliminate
     */
    public function purgeExpired(): int
    {
        $cutoff = now()->subYears(6);

        $count = RadiusSession::where('acct_stop', '<', $cutoff)
            ->where('retention_until', '<', now())
            ->delete();

        Log::info("DataRetention: eliminate {$count} sessioni scadute (retention > 6 anni)");

        return $count;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function lazyQuery(Carbon $from, Carbon $to, ?int $tenantId): LazyCollection
    {
        return RadiusSession::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereBetween('acct_start', [$from, $to])
            ->orderBy('acct_start')
            ->lazyById(1000);
    }

    private function validateRetentionWindow(Carbon $from, Carbon $to): void
    {
        if ($from->isAfter($to)) {
            throw new \InvalidArgumentException('La data di inizio deve essere precedente alla data di fine.');
        }

        if ($to->isAfter(now())) {
            throw new \InvalidArgumentException('La data di fine non può essere nel futuro.');
        }
    }
}
