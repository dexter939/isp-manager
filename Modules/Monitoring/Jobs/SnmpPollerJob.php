<?php

declare(strict_types=1);

namespace Modules\Monitoring\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Monitoring\Events\NetworkAlertCreated;
use Modules\Monitoring\Models\NetworkAlert;
use Modules\Network\Models\BtsStation;

/**
 * Scheduled every 5 minutes via Kernel.
 * Polls active BTS stations via SNMP, creates NetworkAlerts for degraded metrics.
 *
 * Thresholds:
 *   - CCQ (Connection Quality, %) < 80  → severity high
 *   - RSSI (dBm)                 < -75  → severity high
 *   - BTS unreachable (SNMP timeout)    → severity critical
 *
 * OIDs used (Cambium/Ubiquiti standard):
 *   - .1.3.6.1.4.1.161.19.3.3.1.59  airMAX CCQ
 *   - .1.3.6.1.4.1.41112.1.4.7.1.4  Ubiquiti RSSI
 */
class SnmpPollerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 120;

    // CCQ/RSSI thresholds
    private const CCQ_THRESHOLD  = 80;
    private const RSSI_THRESHOLD = -75;

    // SNMP OIDs
    private const OID_CCQ  = '.1.3.6.1.4.1.161.19.3.3.1.59.0';
    private const OID_RSSI = '.1.3.6.1.4.1.41112.1.4.7.1.4.1';

    public function handle(): void
    {
        if ((bool) config('app.carrier_mock', false)) {
            Log::info('[MOCK] SnmpPollerJob: skip — carrier_mock=true');
            return;
        }

        $stations = BtsStation::active()->get();

        foreach ($stations as $bts) {
            try {
                $this->pollStation($bts);
            } catch (\Throwable $e) {
                Log::error("SnmpPollerJob: errore polling BTS #{$bts->id} ({$bts->code}): {$e->getMessage()}");
            }
        }
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function pollStation(BtsStation $bts): void
    {
        if (!$bts->ip_management) {
            return;
        }

        $metrics = $this->snmpGet($bts->ip_management);

        if ($metrics === null) {
            // SNMP timeout — BTS unreachable
            $this->createAlert($bts, 'critical', 'bts_down', "BTS {$bts->code} non raggiungibile via SNMP", [
                'ip' => $bts->ip_management,
            ]);
            return;
        }

        if (isset($metrics['ccq']) && $metrics['ccq'] < self::CCQ_THRESHOLD) {
            $this->createAlert($bts, 'high', 'bts_degraded', "BTS {$bts->code}: CCQ {$metrics['ccq']}% < " . self::CCQ_THRESHOLD . '%', [
                'metric' => 'ccq',
                'value'  => $metrics['ccq'],
                'threshold' => self::CCQ_THRESHOLD,
            ]);
        }

        if (isset($metrics['rssi']) && $metrics['rssi'] < self::RSSI_THRESHOLD) {
            $this->createAlert($bts, 'high', 'bts_degraded', "BTS {$bts->code}: RSSI {$metrics['rssi']} dBm < " . self::RSSI_THRESHOLD . ' dBm', [
                'metric' => 'rssi',
                'value'  => $metrics['rssi'],
                'threshold' => self::RSSI_THRESHOLD,
            ]);
        }
    }

    /**
     * @return array{ccq: int, rssi: int}|null  null on SNMP timeout/unreachable
     */
    private function snmpGet(string $ip): ?array
    {
        $community = config('services.snmp.community', 'public');
        $timeout   = (int) config('services.snmp.timeout_ms', 2000);

        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

        $result = @snmpget($ip, $community, self::OID_CCQ, $timeout, 1);

        if ($result === false) {
            return null;
        }

        $ccq  = (int) preg_replace('/\D+/', '', $result);
        $rssi = (int) preg_replace('/\D+/', '', @snmpget($ip, $community, self::OID_RSSI, $timeout, 1) ?: '0');

        return ['ccq' => $ccq, 'rssi' => $rssi];
    }

    private function createAlert(BtsStation $bts, string $severity, string $type, string $message, array $details): void
    {
        // Avoid duplicate open alerts for same BTS+type
        $existing = NetworkAlert::where('tenant_id', $bts->tenant_id)
            ->where('type', $type)
            ->where('status', 'open')
            ->whereJsonContains('details->bts_id', $bts->id)
            ->first();

        if ($existing) {
            return;
        }

        $alert = NetworkAlert::create([
            'tenant_id' => $bts->tenant_id,
            'source'    => 'snmp',
            'severity'  => $severity,
            'type'      => $type,
            'message'   => $message,
            'status'    => 'open',
            'details'   => array_merge($details, ['bts_id' => $bts->id, 'bts_code' => $bts->code]),
        ]);

        NetworkAlertCreated::dispatch($alert);

        Log::warning("SnmpPoller: {$message}");
    }
}
