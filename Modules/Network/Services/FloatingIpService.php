<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Network\Enums\FloatingIpEventType;
use Modules\Network\Enums\FloatingIpStatus;
use Modules\Network\Events\FloatingIpFailoverTriggered;
use Modules\Network\Events\FloatingIpRecoveryTriggered;
use Modules\Network\Models\FloatingIpEvent;
use Modules\Network\Models\FloatingIpPair;
use Modules\Network\Models\FloatingIpResource;

class FloatingIpService
{
    public function __construct(
        private readonly CoaService $coaService,
        private readonly RadiusService $radiusService,
    ) {}

    /**
     * Create a floating IP pair with its associated resources.
     * Validates that master and failover accounts are distinct.
     *
     * @param array<string, mixed> $data
     */
    public function createPair(array $data): FloatingIpPair
    {
        if ($data['master_pppoe_account_id'] === $data['failover_pppoe_account_id']) {
            throw new \InvalidArgumentException(
                'master_pppoe_account_id and failover_pppoe_account_id must be different.'
            );
        }

        return DB::transaction(function () use ($data): FloatingIpPair {
            $pair = FloatingIpPair::create([
                'tenant_id'                 => $data['tenant_id'],
                'name'                      => $data['name'],
                'master_pppoe_account_id'   => $data['master_pppoe_account_id'],
                'failover_pppoe_account_id' => $data['failover_pppoe_account_id'],
                'status'                    => FloatingIpStatus::MasterActive,
            ]);

            foreach ($data['resources'] ?? [] as $resource) {
                FloatingIpResource::create([
                    'floating_ip_pair_id' => $pair->id,
                    'resource_type'       => $resource['resource_type'],
                    'resource_value'      => $resource['resource_value'],
                ]);
            }

            Log::info('[FloatingIp] Pair created', [
                'pair_id'   => $pair->id,
                'tenant_id' => $pair->tenant_id,
                'name'      => $pair->name,
            ]);

            return $pair->load('resources');
        });
    }

    /**
     * Trigger failover: move all floating IPs from master to failover account.
     * Guarded by status check (only acts when status == master_active).
     */
    public function triggerFailover(FloatingIpPair $pair, string $reason = 'radius_disconnect'): void
    {
        if ($pair->status !== FloatingIpStatus::MasterActive) {
            Log::info('[FloatingIp] triggerFailover skipped — pair not in master_active', [
                'pair_id' => $pair->id,
                'status'  => $pair->status->value,
            ]);
            return;
        }

        if (config('app.carrier_mock', false)) {
            Log::info('[FloatingIp][MOCK] triggerFailover — logging only, skipping CoA', [
                'pair_id' => $pair->id,
                'reason'  => $reason,
            ]);
            $this->logEvent($pair, FloatingIpEventType::FailoverTriggered, $reason, FloatingIpStatus::MasterActive, FloatingIpStatus::FailoverActive);
            return;
        }

        DB::transaction(function () use ($pair, $reason): void {
            $pair->load('resources');

            // 1. Update radreply entries for failover account with IP attributes
            foreach ($pair->resources as $resource) {
                $this->updateRadreplyForAccount(
                    $pair->failover_pppoe_account_id,
                    $resource
                );
            }

            // 2. Send CoA disconnect to master account NAS
            $this->sendDisconnectToAccount($pair->master_pppoe_account_id, 'master failover disconnect');

            $previousStatus = $pair->status;

            // 3. Update pair status
            $pair->update([
                'status'           => FloatingIpStatus::FailoverActive,
                'last_failover_at' => now(),
            ]);

            // 4. Log the event
            $this->logEvent($pair, FloatingIpEventType::FailoverTriggered, $reason, $previousStatus, FloatingIpStatus::FailoverActive);

            Log::info('[FloatingIp] Failover triggered', [
                'pair_id' => $pair->id,
                'reason'  => $reason,
            ]);
        });

        // 5. Fire event (outside transaction to avoid blocking)
        event(new FloatingIpFailoverTriggered($pair->fresh(), $reason));
    }

    /**
     * Trigger recovery: move all floating IPs back from failover to master account.
     * Only acts when status == failover_active and master has an active radacct session.
     */
    public function triggerRecovery(FloatingIpPair $pair): void
    {
        if ($pair->status !== FloatingIpStatus::FailoverActive) {
            Log::info('[FloatingIp] triggerRecovery skipped — pair not in failover_active', [
                'pair_id' => $pair->id,
                'status'  => $pair->status->value,
            ]);
            return;
        }

        // Verify master is actually online before recovering
        $masterSession = $this->getActiveRadacctSession($pair->master_pppoe_account_id);
        if (!$masterSession) {
            Log::info('[FloatingIp] triggerRecovery aborted — master has no active radacct session', [
                'pair_id'                  => $pair->id,
                'master_pppoe_account_id'  => $pair->master_pppoe_account_id,
            ]);
            return;
        }

        if (config('app.carrier_mock', false)) {
            Log::info('[FloatingIp][MOCK] triggerRecovery — logging only, skipping CoA', [
                'pair_id' => $pair->id,
            ]);
            $this->logEvent($pair, FloatingIpEventType::RecoveryTriggered, 'recovery', FloatingIpStatus::FailoverActive, FloatingIpStatus::MasterActive);
            return;
        }

        DB::transaction(function () use ($pair): void {
            $pair->load('resources');

            // 1. Restore radreply attributes back to master account
            foreach ($pair->resources as $resource) {
                $this->updateRadreplyForAccount(
                    $pair->master_pppoe_account_id,
                    $resource
                );
            }

            // 2. Send CoA disconnect to failover account to force re-auth
            $this->sendDisconnectToAccount($pair->failover_pppoe_account_id, 'recovery failover disconnect');

            $previousStatus = $pair->status;

            // 3. Update pair status
            $pair->update([
                'status'            => FloatingIpStatus::MasterActive,
                'last_recovery_at'  => now(),
            ]);

            // 4. Log the event
            $this->logEvent($pair, FloatingIpEventType::RecoveryTriggered, 'recovery', $previousStatus, FloatingIpStatus::MasterActive);

            Log::info('[FloatingIp] Recovery triggered', ['pair_id' => $pair->id]);
        });

        // 5. Fire event (outside transaction)
        event(new FloatingIpRecoveryTriggered($pair->fresh(), 'recovery'));
    }

    /**
     * Get current status, last 10 events, and master/failover session info.
     *
     * @return array<string, mixed>
     */
    public function getStatus(FloatingIpPair $pair): array
    {
        $pair->load('resources');

        $lastEvents = $pair->events()->orderByDesc('created_at')->limit(10)->get();

        $masterSession   = $this->getActiveRadacctSession($pair->master_pppoe_account_id);
        $failoverSession = $this->getActiveRadacctSession($pair->failover_pppoe_account_id);

        return [
            'pair'             => $pair,
            'status'           => $pair->status,
            'last_failover_at' => $pair->last_failover_at,
            'last_recovery_at' => $pair->last_recovery_at,
            'last_events'      => $lastEvents,
            'master_session'   => $masterSession,
            'failover_session' => $failoverSession,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Update the radreply table to assign the floating IP resource to the given account.
     */
    private function updateRadreplyForAccount(string $pppoeAccountId, FloatingIpResource $resource): void
    {
        // Map resource type to RADIUS attribute name
        $attribute = match ($resource->resource_type->value) {
            'ipv4'        => 'Framed-IP-Address',
            'ipv4_subnet' => 'Framed-Route',
            'ipv6_prefix' => 'Framed-IPv6-Prefix',
            default       => 'Framed-IP-Address',
        };

        DB::table('radreply')
            ->updateOrInsert(
                ['username' => $pppoeAccountId, 'attribute' => $attribute],
                ['op' => '=', 'value' => $resource->resource_value]
            );

        Log::info('[FloatingIp] radreply updated', [
            'account_id' => $pppoeAccountId,
            'attribute'  => $attribute,
            'value'      => $resource->resource_value,
        ]);
    }

    /**
     * Send a CoA Disconnect-Request to the NAS serving the given PPPoE account.
     */
    private function sendDisconnectToAccount(string $pppoeAccountId, string $context): void
    {
        // Look up the active radius user for this PPPoE account
        $radiusUser = \Modules\Network\Models\RadiusUser::where('username', $pppoeAccountId)
            ->orWhere('id', $pppoeAccountId)
            ->first();

        if (!$radiusUser) {
            Log::warning("[FloatingIp] sendDisconnect: no RadiusUser found for account {$pppoeAccountId} ({$context})");
            return;
        }

        $this->coaService->disconnect($radiusUser);
    }

    /**
     * Query radacct for the latest active session of the given PPPoE account.
     *
     * @return array<string, mixed>|null
     */
    private function getActiveRadacctSession(string $pppoeAccountId): ?array
    {
        $row = DB::selectOne(
            'SELECT * FROM radacct WHERE username = ? AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1',
            [$pppoeAccountId]
        );

        return $row ? (array) $row : null;
    }

    /**
     * Append an event record to floating_ip_events.
     */
    private function logEvent(
        FloatingIpPair $pair,
        FloatingIpEventType $eventType,
        string $triggeredBy,
        FloatingIpStatus $previousStatus,
        FloatingIpStatus $newStatus,
        ?string $notes = null,
    ): void {
        FloatingIpEvent::create([
            'floating_ip_pair_id' => $pair->id,
            'event_type'          => $eventType,
            'triggered_by'        => $triggeredBy,
            'previous_status'     => $previousStatus->value,
            'new_status'          => $newStatus->value,
            'notes'               => $notes,
        ]);
    }
}
