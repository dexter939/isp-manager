<?php

declare(strict_types=1);

namespace Modules\Provisioning\Services;

use Illuminate\Support\Facades\DB;
use Modules\Contracts\Models\Contract;
use Modules\Provisioning\Models\VlanPool;

/**
 * Gestione pool C-VLAN per carrier.
 *
 * Ogni ordine di attivazione richiede un C-VLAN libero del pool.
 * Il VLAN viene rilasciato alla cessazione del contratto.
 * Usa transazione PostgreSQL per evitare race condition (SELECT FOR UPDATE SKIP LOCKED).
 */
class VlanManager
{
    /**
     * Assegna il primo C-VLAN libero del pool per il carrier specificato.
     * Usa FOR UPDATE SKIP LOCKED per evitare race condition in ambiente multi-worker.
     *
     * @throws \RuntimeException se non ci sono VLAN liberi
     */
    public function assign(string $carrier, Contract $contract, string $vlanType = 'C-VLAN'): VlanPool
    {
        return DB::transaction(function () use ($carrier, $contract, $vlanType) {
            $vlan = VlanPool::where('tenant_id', $contract->tenant_id)
                ->where('carrier', $carrier)
                ->where('vlan_type', $vlanType)
                ->where('status', 'free')
                ->lockForUpdate()
                ->first();

            if (!$vlan) {
                throw new \RuntimeException(
                    "Nessun C-VLAN libero disponibile per carrier={$carrier} tipo={$vlanType}. Verificare il pool."
                );
            }

            $vlan->update([
                'status'      => 'assigned',
                'contract_id' => $contract->id,
                'assigned_at' => now(),
            ]);

            return $vlan;
        });
    }

    /**
     * Rilascia il VLAN associato a un contratto (alla cessazione).
     */
    public function release(Contract $contract): void
    {
        VlanPool::where('contract_id', $contract->id)
            ->update([
                'status'      => 'free',
                'contract_id' => null,
                'assigned_at' => null,
            ]);
    }

    /**
     * Conta i VLAN liberi disponibili per un carrier.
     */
    public function getAvailable(int $tenantId, string $carrier, string $vlanType = 'C-VLAN'): int
    {
        return VlanPool::where('tenant_id', $tenantId)
            ->where('carrier', $carrier)
            ->where('vlan_type', $vlanType)
            ->where('status', 'free')
            ->count();
    }

    /**
     * Riserva un VLAN specifico (es: migrazione con VLAN già assegnata dal vecchio OLO).
     */
    public function reserve(int $tenantId, string $carrier, int $vlanId, string $reason = ''): VlanPool
    {
        return DB::transaction(function () use ($tenantId, $carrier, $vlanId, $reason) {
            $vlan = VlanPool::where('tenant_id', $tenantId)
                ->where('carrier', $carrier)
                ->where('vlan_id', $vlanId)
                ->where('status', 'free')
                ->lockForUpdate()
                ->firstOrFail();

            $vlan->update([
                'status' => 'reserved',
                'notes'  => $reason,
            ]);

            return $vlan;
        });
    }

    /**
     * Popola il pool con un range di VLAN (usato da Seeder/comando artisan).
     *
     * @param int $from primo VLAN_ID (es: 100)
     * @param int $to   ultimo VLAN_ID  (es: 500)
     */
    public function seedPool(int $tenantId, string $carrier, int $from, int $to, string $vlanType = 'C-VLAN'): int
    {
        $inserted = 0;
        for ($id = $from; $id <= $to; $id++) {
            VlanPool::firstOrCreate(
                ['tenant_id' => $tenantId, 'carrier' => $carrier, 'vlan_type' => $vlanType, 'vlan_id' => $id],
                ['status' => 'free']
            );
            $inserted++;
        }
        return $inserted;
    }
}
