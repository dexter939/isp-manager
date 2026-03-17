<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HardwareAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'type'                     => $this->type,
            'brand'                    => $this->brand,
            'model'                    => $this->model,
            'serial_number'            => $this->serial_number,
            'mac_address'              => $this->mac_address,
            'qr_code'                  => $this->qr_code,
            'status'                   => $this->status,
            'contract_id'              => $this->contract_id,
            'assigned_by'              => $this->assigned_by,
            'assigned_at'              => $this->assigned_at?->toIso8601String(),
            'returned_at'              => $this->returned_at?->toIso8601String(),
            'purchase_date'            => $this->purchase_date?->toIso8601String(),
            'warranty_expires'         => $this->warranty_expires?->toIso8601String(),
            'is_under_warranty'        => $this->isUnderWarranty(),
            'created_at'               => $this->created_at?->toIso8601String(),
            'updated_at'               => $this->updated_at?->toIso8601String(),
        ];
    }
}
