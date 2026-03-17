<?php

namespace Modules\Maintenance\PurchaseOrders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'supplier'         => $this->whenLoaded('supplier', fn() => new SupplierResource($this->supplier)),
            'status'           => $this->status?->value,
            'reference_number' => $this->reference_number,
            'notes'            => $this->notes,
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'received_at'      => $this->received_at?->toIso8601String(),
            'items'            => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'                  => $item->id,
                'inventory_model_id'  => $item->inventory_model_id,
                'quantity_ordered'    => $item->quantity_ordered,
                'quantity_received'   => $item->quantity_received,
                'unit_price'          => $item->unit_price_amount !== null ? [
                    'amount'    => $item->unit_price_amount,
                    'currency'  => $item->unit_price_currency,
                    'formatted' => '€' . number_format($item->unit_price_amount / 100, 2, ',', '.'),
                ] : null,
            ])),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
