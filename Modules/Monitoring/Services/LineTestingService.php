<?php

declare(strict_types=1);

namespace Modules\Monitoring\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\ApiQuotaManager;
use Modules\Contracts\Models\Contract;
use Modules\Monitoring\Events\NetworkAlertCreated;
use Modules\Monitoring\Models\LineTestResult;
use Modules\Monitoring\Models\NetworkAlert;
use Modules\Provisioning\Services\FiberCopTokenService;

/**
 * Line Testing Service — Open Fiber API v2.3 + FiberCop NGASP.
 *
 * Spec Open Fiber (v2.3, 20/02/2026):
 *   GET /linetesting?ResourceId={CORD_o_UI}&SourceSystem={OLO_CODE}
 *   Header: lt-api-key: {token}  ← NOTA v2.3: non più "tokenID"
 *   Cache TTL: 6 ore
 *
 * Codici errore v2.3:
 *   L01 = Service not available → retry con backoff
 *   L02 = Generic error / Timeout / ONT unreachable
 *     → "timeout" in Description → retry (30s, 5min, 30min)
 *     → "unreachable" in Description → NON retry, aprire ticket assurance
 *   L03 = Bad formatted request → bug, non retry
 *   L04 = Resource ID not found → verificare codice_ui
 *   L05 = Rate limit (HTTP 427) → ApiQuotaManager
 *   L06 = Unauthorized → lt-api-key invalido
 *   L07 = Massive fault (MSO) → alert speciale, NON ticket singolo
 *
 * FiberCop NGASP:
 *   statusZpoint → stato ONT lato cliente
 *   startDegradeMeasure + readDegradeMeasure → misura degrado (asincrono)
 */
class LineTestingService
{
    private const CACHE_TTL = 6 * 3600;    // 6 ore (CLAUDE_CONTEXT)

    private bool $isMocked;

    public function __construct(
        private readonly ApiQuotaManager $quotaManager,
        private readonly FiberCopTokenService $fiberCopTokenService,
    ) {
        $this->isMocked = (bool) config('app.carrier_mock', false);
    }

    /**
     * Esegue un line test Open Fiber su una linea FTTH.
     * Ritorna il risultato e lo persiste in line_test_results.
     *
     * @return LineTestResult
     */
    public function testOpenFiber(Contract $contract, string $initiatedBy = 'system'): LineTestResult
    {
        $resourceId = $contract->codice_ui ?? $contract->id_building;

        if (!$resourceId) {
            throw new \InvalidArgumentException("Contratto #{$contract->id}: nessun codice_ui o id_building disponibile");
        }

        // Controlla quota giornaliera (L05 prevention)
        if (!$this->quotaManager->canCall('openfiber', 'line_test')) {
            throw new \RuntimeException('Quota giornaliera line testing Open Fiber esaurita');
        }

        // Controlla cache (6h TTL)
        $cacheKey = "lt:of:{$resourceId}";
        if ($cached = Cache::get($cacheKey)) {
            Log::debug("Line test OF: risposta da cache per {$resourceId}");
            return $this->persistResult($contract, 'openfiber', $resourceId, $cached, $initiatedBy, fromCache: true);
        }

        $rawResponse = $this->callOpenFiberApi($resourceId);

        // Cache before consuming quota: if cache write fails, quota is not charged
        Cache::put($cacheKey, $rawResponse, self::CACHE_TTL);
        $this->quotaManager->consume('openfiber', 'line_test');

        return $this->persistResult($contract, 'openfiber', $resourceId, $rawResponse, $initiatedBy);
    }

    /**
     * Esegue statusZpoint FiberCop NGASP (stato ONT lato cliente).
     */
    public function testFiberCop(Contract $contract, string $initiatedBy = 'system'): LineTestResult
    {
        if (!$contract->codice_ui) {
            throw new \InvalidArgumentException("Contratto #{$contract->id}: codice_ui mancante per FiberCop");
        }

        if (!$this->quotaManager->canCall('fibercop', 'status_zpoint')) {
            throw new \RuntimeException('Quota FiberCop statusZpoint esaurita');
        }

        $cacheKey = "lt:fc:{$contract->codice_ui}";
        if ($cached = Cache::get($cacheKey)) {
            return $this->persistResult($contract, 'fibercop', $contract->codice_ui, $cached, $initiatedBy, fromCache: true);
        }

        $rawResponse = $this->callFiberCopStatusZpoint($contract->codice_ui);

        Cache::put($cacheKey, $rawResponse, 2 * 3600);
        $this->quotaManager->consume('fibercop', 'status_zpoint'); // 2h (CLAUDE_CONTEXT: status_zpoint TTL)

        return $this->persistResult($contract, 'fibercop', $contract->codice_ui, $rawResponse, $initiatedBy);
    }

    // ── API calls ────────────────────────────────────────────────────────────

    /**
     * Chiama la Line Testing REST API Open Fiber v2.3.
     * Header: lt-api-key (non tokenID — novità v2.3)
     */
    private function callOpenFiberApi(string $resourceId): array
    {
        if ($this->isMocked) {
            Log::info("[MOCK] OF Line Test per ResourceId={$resourceId}");
            return [
                'TestInstanceId'      => rand(1000, 9999),
                'Result'              => 'OK',
                'OntOperationalState' => 'UP',
                'Attenuation'         => '-12.5',
                'OpticalDistance'     => '850.0',
                'OntLanStatus'        => 'ENABLED',
            ];
        }

        $apiKey     = config('services.openfiber.lt_api_key');
        $oloCode    = config('services.openfiber.olo_code');
        $baseUrl    = config('services.openfiber.lt_base_url', 'https://api.openfiber.it');

        $response = Http::withHeaders([
            'lt-api-key' => $apiKey,   // v2.3: lt-api-key (non tokenID)
        ])->timeout(30)->get("{$baseUrl}/linetesting", [
            'ResourceId'   => $resourceId,
            'SourceSystem' => $oloCode,
        ]);

        if ($response->status() === 427) {
            // L05 — rate limit (HTTP 427 specifico OF v2.3)
            throw new \RuntimeException('Open Fiber line test: L05 rate limit (HTTP 427)');
        }

        if ($response->status() === 401) {
            throw new \RuntimeException('Open Fiber line test: L06 Unauthorized — lt-api-key invalido');
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Chiama FiberCop NGASP statusZpoint.
     * Auth: OAuth2 Bearer (gestito da FiberCopDriver del modulo Provisioning).
     */
    private function callFiberCopStatusZpoint(string $codiceUi): array
    {
        if ($this->isMocked) {
            Log::info("[MOCK] FiberCop statusZpoint per codiceUi={$codiceUi}");
            return [
                'result'    => 'OK',
                'zpoint'    => ['status' => 'UP', 'ont_id' => $codiceUi],
                'timestamp' => now()->toIso8601String(),
            ];
        }

        $baseUrl = config('services.fibercop.ngasp_base_url');

        $response = Http::withToken($this->fiberCopTokenService->getToken())
            ->timeout(30)
            ->post("{$baseUrl}/statusZpoint", ['codiceUI' => $codiceUi]);

        if ($response->status() === 401) {
            // Token scaduto → forza rinnovo
            $response = Http::withToken($this->fiberCopTokenService->refreshToken())
                ->timeout(30)
                ->post("{$baseUrl}/statusZpoint", ['codiceUI' => $codiceUi]);
        }

        $response->throw();
        return $response->json();
    }

    // ── Result persistence + error handling ──────────────────────────────────

    private function persistResult(
        Contract $contract,
        string $carrier,
        string $resourceId,
        array $raw,
        string $initiatedBy,
        bool $fromCache = false,
    ): LineTestResult {
        $parsed = $carrier === 'openfiber'
            ? $this->parseOpenFiberResponse($raw)
            : $this->parseFiberCopResponse($raw);

        $result = LineTestResult::create([
            'tenant_id'          => $contract->tenant_id,
            'contract_id'        => $contract->id,
            'customer_id'        => $contract->customer_id,
            'carrier'            => $carrier,
            'resource_id'        => $resourceId,
            'result'             => $parsed['result'],
            'error_code'         => $parsed['error_code'] ?? null,
            'ont_state'          => $parsed['ont_state'] ?? null,
            'attenuation_dbm'    => $parsed['attenuation'] ?? null,
            'optical_distance_m' => $parsed['optical_distance'] ?? null,
            'ont_lan_status'     => $parsed['ont_lan_status'] ?? null,
            'test_instance_id'   => $raw['TestInstanceId'] ?? null,
            'is_retryable'       => $parsed['is_retryable'] ?? false,
            'triggered_ticket'   => false,
            'raw_response'       => $raw,
            'initiated_by'       => $initiatedBy,
        ]);

        // Gestisci errori specifici v2.3
        if ($parsed['result'] === 'KO') {
            $this->handleKoResult($contract, $result, $parsed);
        }

        return $result;
    }

    /**
     * Parsa la risposta Open Fiber v2.3 e determina is_retryable.
     */
    private function parseOpenFiberResponse(array $raw): array
    {
        if ($raw['Result'] === 'OK') {
            return [
                'result'          => 'OK',
                'ont_state'       => $raw['OntOperationalState'] ?? null,
                'attenuation'     => isset($raw['Attenuation']) ? (float) $raw['Attenuation'] : null,
                'optical_distance' => isset($raw['OpticalDistance']) ? (float) $raw['OpticalDistance'] : null,
                'ont_lan_status'  => $raw['OntLanStatus'] ?? null,
            ];
        }

        $code        = $raw['Code'] ?? '';
        $description = strtolower($raw['Description'] ?? '');

        // L02 v2.3: distingue timeout (retry) da unreachable (ticket)
        $isRetryable = match($code) {
            'L01' => true,                              // Service not available
            'L02' => str_contains($description, 'timeout'),  // solo se timeout
            default => false,
        };

        $needsTicket = $code === 'L02' && str_contains($description, 'unreachable');

        return [
            'result'       => 'KO',
            'error_code'   => $code,
            'is_retryable' => $isRetryable,
            'needs_ticket' => $needsTicket,
            'is_mso'       => $code === 'L07',
        ];
    }

    private function parseFiberCopResponse(array $raw): array
    {
        $ok = ($raw['result'] ?? '') === 'OK';
        return [
            'result'    => $ok ? 'OK' : 'KO',
            'ont_state' => $ok ? ($raw['zpoint']['status'] ?? null) : null,
        ];
    }

    /**
     * Gestisce i risultati KO generando alert appropriati.
     */
    private function handleKoResult(Contract $contract, LineTestResult $result, array $parsed): void
    {
        if ($parsed['is_mso'] ?? false) {
            // L07 — Massive Fault: alert speciale, NON aprire ticket singolo
            $this->createAlert($contract, $result, 'l07_mso', 'critical',
                "MSO rilevato (Massive Service Outage) su ResourceId {$result->resource_id}. NON aprire ticket singolo."
            );
            return;
        }

        if ($parsed['needs_ticket'] ?? false) {
            // L02 unreachable: ONT irraggiungibile → ticket assurance
            $this->createAlert($contract, $result, 'l02_unreachable', 'critical',
                "ONT irraggiungibile (L02 unreachable) — aprire ticket assurance."
            );
            $result->update(['triggered_ticket' => true]);
            return;
        }

        if (($parsed['error_code'] ?? '') === 'L04') {
            // Resource ID non trovato: warn operatore
            $this->createAlert($contract, $result, 'ont_offline', 'warning',
                "L04: ResourceId {$result->resource_id} non trovato su Open Fiber — verificare codice_ui."
            );
        }
    }

    private function createAlert(Contract $contract, LineTestResult $result, string $type, string $severity, string $message): void
    {
        $alert = NetworkAlert::create([
            'tenant_id'   => $contract->tenant_id,
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'source'      => "line_test_{$result->carrier}",
            'severity'    => $severity,
            'type'        => $type,
            'message'     => $message,
            'details'     => $result->raw_response,
            'status'      => 'open',
        ]);

        NetworkAlertCreated::dispatch($alert);
    }
}
