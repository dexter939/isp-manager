<?php

declare(strict_types=1);

namespace Modules\Network\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Network\Enums\FloatingIpStatus;
use Modules\Network\Models\FloatingIpPair;
use Modules\Network\Services\FloatingIpService;

class FloatingIpMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function handle(FloatingIpService $floatingIpService): void
    {
        if (config('app.carrier_mock', false)) {
            Log::info('[FloatingIpMonitor][MOCK] Skipping monitor run in carrier_mock mode.');
            return;
        }

        $offlineThresholdMinutes = (int) config('floating_ip.offline_threshold_minutes', 5);
        $gracePeriodMinutes      = (int) config('floating_ip.grace_period_minutes', 2);
        $autoRecoveryEnabled     = (bool) config('floating_ip.auto_recovery_enabled', true);

        $pairs = FloatingIpPair::active()->get();

        foreach ($pairs as $pair) {
            try {
                $this->processPair($pair, $floatingIpService, $offlineThresholdMinutes, $gracePeriodMinutes, $autoRecoveryEnabled);
            } catch (\Throwable $e) {
                Log::error('[FloatingIpMonitor] Error processing pair', [
                    'pair_id' => $pair->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    private function processPair(
        FloatingIpPair $pair,
        FloatingIpService $floatingIpService,
        int $offlineThresholdMinutes,
        int $gracePeriodMinutes,
        bool $autoRecoveryEnabled,
    ): void {
        $masterSession = DB::selectOne(
            'SELECT * FROM radacct WHERE username = ? AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1',
            [$pair->master_pppoe_account_id]
        );

        if ($pair->status === FloatingIpStatus::MasterActive) {
            // Master should be online; check if it has gone offline
            if (!$masterSession) {
                // No active session — check how long master has been offline
                $lastStop = DB::selectOne(
                    'SELECT acctstoptime FROM radacct WHERE username = ? AND acctstoptime IS NOT NULL ORDER BY acctstoptime DESC LIMIT 1',
                    [$pair->master_pppoe_account_id]
                );

                $offlineSince = $lastStop
                    ? \Carbon\Carbon::parse($lastStop->acctstoptime)
                    : ($pair->last_failover_at ?? now()->subMinutes($offlineThresholdMinutes + 1));

                $offlineMinutes = $offlineSince->diffInMinutes(now());

                if ($offlineMinutes >= $offlineThresholdMinutes) {
                    Log::info('[FloatingIpMonitor] Master offline threshold reached, triggering failover', [
                        'pair_id'         => $pair->id,
                        'offline_minutes' => $offlineMinutes,
                    ]);
                    $floatingIpService->triggerFailover($pair, 'radius_disconnect');
                }
            }
            return;
        }

        if ($pair->status === FloatingIpStatus::FailoverActive && $autoRecoveryEnabled) {
            // Check if master has come back online
            if ($masterSession) {
                // Master is online — check if it has been stable for the grace period
                $onlineSince = \Carbon\Carbon::parse($masterSession->acctstarttime);
                $onlineMinutes = $onlineSince->diffInMinutes(now());

                if ($onlineMinutes >= $gracePeriodMinutes) {
                    Log::info('[FloatingIpMonitor] Master back online after grace period, triggering recovery', [
                        'pair_id'        => $pair->id,
                        'online_minutes' => $onlineMinutes,
                    ]);
                    $floatingIpService->triggerRecovery($pair);
                } else {
                    Log::info('[FloatingIpMonitor] Master back online but grace period not elapsed', [
                        'pair_id'        => $pair->id,
                        'online_minutes' => $onlineMinutes,
                        'grace_period'   => $gracePeriodMinutes,
                    ]);
                }
            }
        }
    }
}
