<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'sku'                => $this->sku,
            'name'               => $this->name,
            'category'           => $this->category,
            'description'        => $this->description,
            'unit'               => $this->unit,
            'quantity'           => $this->quantity,
            'quantity_reserved'  => $this->quantity_reserved,
            'quantity_available' => $this->availableQuantity(),
            'reorder_threshold'  => $this->reorder_threshold,
            'is_low_stock'       => $this->isLowStock(),
            'supplier'           => $this->supplier,
            'location'           => $this->location,
            'is_active'          => $this->is_active,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
