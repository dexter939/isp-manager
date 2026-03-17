<?php

declare(strict_types=1);

namespace Modules\Monitoring\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Network\Models\CpeDevice;
use Modules\Monitoring\Models\Tr069Parameter;

/**
 * Interfaccia con GenieACS (ACS server TR-069).
 *
 * GenieACS espone una REST API su porta 7557.
 * Documentazione: https://github.com/genieacs/genieacs/wiki/API-Reference
 *
 * Operazioni supportate:
 *   - getParameters: legge parametri dal CPE (tramite GenieACS task)
 *   - setParameters: scrive parametri sul CPE
 *   - reboot: riavvia il CPE
 *   - factoryReset: reset di fabbrica
 *   - getFirmwareVersion: legge la versione firmware
 *   - upgradeFireware: aggiorna il firmware (via download task)
 */
class TR069Service
{
    private bool $isMocked;
    private string $genieUrl;
    private string $genieUser;
    private string $geniePass;

    public function __construct()
    {
        $this->isMocked  = (bool) config('app.carrier_mock', false);
        $this->genieUrl  = config('services.genieacs.url', 'http://localhost:7557');
        $this->genieUser = config('services.genieacs.username', 'admin');
        $this->geniePass = config('services.genieacs.password', '');
    }

    /**
     * Legge e persiste un set di parametri TR-069 dal CPE.
     *
     * @param string[] $parameterPaths es. ['Device.DeviceInfo.SoftwareVersion', ...]
     * @return array<string, string> mappa path → value
     */
    public function getParameters(CpeDevice $device, array $parameterPaths): array
    {
        if (!$device->tr069_id) {
            throw new \InvalidArgumentException("CPE #{$device->id}: nessun tr069_id configurato");
        }

        if ($this->isMocked) {
            Log::info("[MOCK] TR-069 getParameters per {$device->tr069_id}");
            return array_fill_keys($parameterPaths, 'mock_value');
        }

        // Crea un task GenieACS di tipo "getParameterValues"
        $task = $this->createTask($device->tr069_id, [
            'name'           => 'getParameterValues',
            'parameterNames' => $parameterPaths,
        ]);

        $result = $this->waitForTask($task['_id'], 15);

        // Persisti i parametri
        foreach ($result as $path => $value) {
            Tr069Parameter::updateOrCreate(
                ['cpe_device_id' => $device->id, 'parameter_path' => $path],
                ['value' => (string) $value, 'fetched_at' => now()],
            );
        }

        // Aggiorna last_seen_at CPE
        $device->update(['last_seen_at' => now()]);

        return $result;
    }

    /**
     * Imposta uno o più parametri TR-069 sul CPE.
     *
     * @param array<string, mixed> $parameters mappa path → valore
     */
    public function setParameters(CpeDevice $device, array $parameters): void
    {
        if ($this->isMocked) {
            Log::info("[MOCK] TR-069 setParameters per {$device->tr069_id}", $parameters);
            return;
        }

        $this->createTask($device->tr069_id, [
            'name'       => 'setParameterValues',
            'parameterValues' => array_map(
                fn($k, $v) => [$k, $v, 'xsd:string'],
                array_keys($parameters),
                array_values($parameters),
            ),
        ]);
    }

    /**
     * Riavvia il CPE tramite TR-069 Reboot RPC.
     */
    public function reboot(CpeDevice $device): void
    {
        if ($this->isMocked) {
            Log::info("[MOCK] TR-069 reboot per {$device->tr069_id}");
            return;
        }

        $this->createTask($device->tr069_id, ['name' => 'reboot']);
        Log::info("TR-069: reboot inviato per CPE #{$device->id} ({$device->tr069_id})");
    }

    /**
     * Legge la versione firmware dal CPE.
     */
    public function getFirmwareVersion(CpeDevice $device): ?string
    {
        // Parametro standard TR-098 / TR-181
        $paths = [
            'InternetGatewayDevice.DeviceInfo.SoftwareVersion',
            'Device.DeviceInfo.SoftwareVersion',
        ];

        foreach ($paths as $path) {
            $result = $this->getParameters($device, [$path]);
            if (!empty($result[$path]) && $result[$path] !== 'mock_value') {
                $version = $result[$path];
                $device->update(['firmware_version' => $version]);
                return $version;
            }
        }

        return null;
    }

    /**
     * Riceve l'Inform da GenieACS (webhook) e aggiorna il CPE.
     */
    public function processInform(string $deviceId, array $parameters): void
    {
        $device = CpeDevice::where('tr069_id', $deviceId)->first();

        if (!$device) {
            Log::warning("TR-069 Inform: CPE con tr069_id={$deviceId} non trovato");
            return;
        }

        $device->update([
            'tr069_last_inform' => now(),
            'last_seen_at'      => now(),
            'wan_ip'            => $parameters['ExternalIPAddress'] ?? $device->wan_ip,
            'firmware_version'  => $parameters['SoftwareVersion'] ?? $device->firmware_version,
        ]);
    }

    // ── GenieACS API private ─────────────────────────────────────────────────

    private function createTask(string $deviceId, array $task): array
    {
        $response = Http::withBasicAuth($this->genieUser, $this->geniePass)
            ->timeout(10)
            ->post("{$this->genieUrl}/devices/{$deviceId}/tasks", $task);

        $response->throw();
        return $response->json();
    }

    /**
     * Attende il completamento di un task GenieACS (polling).
     *
     * @return array<string, mixed>
     */
    private function waitForTask(string $taskId, int $maxWaitSeconds = 30): array
    {
        $start = time();

        while ((time() - $start) < $maxWaitSeconds) {
            $response = Http::withBasicAuth($this->genieUser, $this->geniePass)
                ->get("{$this->genieUrl}/tasks/{$taskId}");

            if ($response->ok()) {
                $task = $response->json();
                if (in_array($task['status'] ?? '', ['done', 'fault'], true)) {
                    return $task['result'] ?? [];
                }
            }

            usleep(500_000); // 500ms polling
        }

        throw new \RuntimeException("TR-069 task {$taskId} timeout dopo {$maxWaitSeconds}s");
    }
}
