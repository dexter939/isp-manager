<?php

declare(strict_types=1);

namespace Modules\Maintenance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Maintenance\Enums\InventoryMovementType;
use Modules\Maintenance\Models\InventoryItem;
use Modules\Maintenance\Models\InventoryMovement;

/**
 * Gestisce il magazzino: movimentazioni, aggiustamenti, alert scorte.
 */
class InventoryService
{
    /**
     * Carica stock (ricezione merce da fornitore).
     */
    public function receive(
        InventoryItem $item,
        int $quantity,
        int $userId,
        string $reference = '',
        string $notes = '',
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantità di carico deve essere positiva");
        }

        return $this->move($item, InventoryMovementType::In, $quantity, $userId, null, $reference, $notes);
    }

    /**
     * Scarica stock (installazione, sostituzione, guasto).
     */
    public function consume(
        InventoryItem $item,
        int $quantity,
        int $userId,
        ?int $ticketId = null,
        string $reference = '',
        string $notes = '',
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantità di scarico deve essere positiva");
        }

        if ($item->availableQuantity() < $quantity) {
            throw new \RuntimeException(
                "Stock insufficiente per {$item->sku}: disponibili {$item->availableQuantity()}, richiesti {$quantity}"
            );
        }

        return $this->move($item, InventoryMovementType::Out, -$quantity, $userId, $ticketId, $reference, $notes);
    }

    /**
     * Rettifica l'inventario (inventario fisico).
     */
    public function adjust(
        InventoryItem $item,
        int $newQuantity,
        int $userId,
        string $notes = 'Rettifica inventario',
    ): InventoryMovement {
        $delta = $newQuantity - $item->quantity;
        return $this->move($item, InventoryMovementType::Adjustment, $delta, $userId, null, '', $notes);
    }

    /**
     * Riserva unità per un'installazione pianificata.
     */
    public function reserve(InventoryItem $item, int $quantity): void
    {
        if ($item->availableQuantity() < $quantity) {
            throw new \RuntimeException("Stock insufficiente per prenotazione {$item->sku}");
        }

        $item->increment('quantity_reserved', $quantity);
    }

    /**
     * Libera una prenotazione precedente.
     */
    public function releaseReservation(InventoryItem $item, int $quantity): void
    {
        $item->decrement('quantity_reserved', min($quantity, $item->quantity_reserved));
    }

    /**
     * Articoli sotto soglia di riordino.
     *
     * @return Collection<InventoryItem>
     */
    public function getLowStock(int $tenantId): Collection
    {
        return InventoryItem::where('tenant_id', $tenantId)
            ->active()
            ->lowStock()
            ->orderBy('quantity')
            ->get();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function move(
        InventoryItem $item,
        InventoryMovementType $type,
        int $delta,
        int $userId,
        ?int $ticketId,
        string $reference,
        string $notes,
    ): InventoryMovement {
        return DB::transaction(function () use ($item, $type, $delta, $userId, $ticketId, $reference, $notes) {
            $quantityBefore = $item->quantity;
            $quantityAfter  = $quantityBefore + $delta;

            if ($quantityAfter < 0) {
                throw new \RuntimeException("Stock non può diventare negativo per {$item->sku}");
            }

            $item->update(['quantity' => $quantityAfter]);

            return InventoryMovement::create([
                'tenant_id'           => $item->tenant_id,
                'inventory_item_id'   => $item->id,
                'user_id'             => $userId,
                'ticket_id'           => $ticketId,
                'type'                => $type->value,
                'quantity'            => $delta,
                'quantity_before'     => $quantityBefore,
                'quantity_after'      => $quantityAfter,
                'reference'           => $reference ?: null,
                'notes'               => $notes ?: null,
                'moved_at'            => now(),
            ]);
        });
    }
}
