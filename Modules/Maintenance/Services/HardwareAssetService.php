<?php

declare(strict_types=1);

namespace Modules\Maintenance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Contracts\Models\Contract;
use Modules\Maintenance\Models\HardwareAsset;

/**
 * Gestisce il registro fisico degli apparati ISP (ONT, router, CPE FWA, SIM).
 *
 * Un apparato = una riga in hardware_assets, identificato univocamente da serial_number.
 * Ciclo di vita: in_stock → assigned (consegna cliente) → in_stock (ritiro) o disposed.
 */
class HardwareAssetService
{
    /**
     * Registra un nuovo apparato in magazzino.
     */
    public function register(
        int $tenantId,
        string $type,
        string $serialNumber,
        array $attributes = [],
    ): HardwareAsset {
        return HardwareAsset::create([
            'tenant_id'      => $tenantId,
            'type'           => $type,
            'serial_number'  => $serialNumber,
            'status'         => 'in_stock',
            'mac_address'    => $attributes['mac_address'] ?? null,
            'qr_code'        => $attributes['qr_code'] ?? null,
            'brand'          => $attributes['brand'] ?? null,
            'model'          => $attributes['model'] ?? null,
            'purchase_price' => $attributes['purchase_price'] ?? null,
            'purchase_date'  => $attributes['purchase_date'] ?? null,
            'warranty_expires' => $attributes['warranty_expires'] ?? null,
            'supplier'       => $attributes['supplier'] ?? null,
            'notes'          => $attributes['notes'] ?? null,
        ]);
    }

    /**
     * Assegna un apparato a un contratto (consegna al cliente).
     */
    public function assign(HardwareAsset $asset, Contract $contract, int $techUserId): void
    {
        if (!$asset->isInStock()) {
            throw new \DomainException(
                "Apparato {$asset->serial_number} non disponibile (stato: {$asset->status})"
            );
        }

        $asset->update([
            'contract_id' => $contract->id,
            'assigned_by' => $techUserId,
            'status'      => 'assigned',
            'assigned_at' => now(),
            'returned_at' => null,
        ]);

        Log::info("Apparato {$asset->serial_number} assegnato al contratto #{$contract->id}");
    }

    /**
     * Ritira l'apparato alla cessazione del contratto.
     */
    public function return(HardwareAsset $asset, int $techUserId): void
    {
        $asset->update([
            'contract_id' => null,
            'assigned_by' => null,
            'status'      => 'in_stock',
            'returned_at' => now(),
        ]);

        Log::info("Apparato {$asset->serial_number} rientrato in magazzino");
    }

    /**
     * Segna l'apparato come in riparazione.
     */
    public function sendToRepair(HardwareAsset $asset, string $notes = ''): void
    {
        $asset->update([
            'status' => 'in_repair',
            'notes'  => $notes ?: $asset->notes,
        ]);
    }

    /**
     * Dismette l'apparato (fine vita, guasto irreparabile).
     */
    public function dispose(HardwareAsset $asset, string $reason = ''): void
    {
        $asset->update([
            'status'  => 'disposed',
            'notes'   => $reason ?: $asset->notes,
            'contract_id' => null,
        ]);
    }

    /**
     * Cerca un apparato tramite QR code (per inventario rapido con scanner).
     */
    public function scanQr(string $qrCode): HardwareAsset
    {
        $asset = HardwareAsset::where('qr_code', $qrCode)->first();

        if (!$asset) {
            throw new \InvalidArgumentException("Apparato con QR code '{$qrCode}' non trovato");
        }

        return $asset;
    }

    /**
     * Trova apparati assegnati non rientrati dopo la cessazione del contratto.
     * Utile per alert tecnici: "recuperare X apparati entro Y giorni".
     *
     * @return Collection<HardwareAsset>
     */
    public function checkUnreturnedItems(int $tenantId, int $afterDays = 30): Collection
    {
        return HardwareAsset::where('tenant_id', $tenantId)
            ->where('status', 'assigned')
            ->where('assigned_at', '<', now()->subDays($afterDays))
            ->whereHas('contract', fn($q) => $q->where('status', 'terminated'))
            ->with('contract.customer')
            ->get();
    }

    /**
     * Statistiche magazzino per tipo.
     */
    public function stockSummary(int $tenantId): array
    {
        return HardwareAsset::where('tenant_id', $tenantId)
            ->selectRaw('type, status, COUNT(*) as count')
            ->groupBy('type', 'status')
            ->get()
            ->groupBy('type')
            ->map(fn($rows) => $rows->pluck('count', 'status'))
            ->toArray();
    }
}
