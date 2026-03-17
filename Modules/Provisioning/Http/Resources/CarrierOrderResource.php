<?php

declare(strict_types=1);

namespace Modules\Provisioning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarrierOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'contract_id'         => $this->contract_id,
            'carrier'             => $this->carrier,
            'order_type'          => $this->order_type?->value,
            'state'               => $this->state?->value,
            'codice_ordine_olo'   => $this->codice_ordine_olo,
            'codice_ordine_of'    => $this->codice_ordine_of,
            'scheduled_date'      => $this->scheduled_date?->toIso8601String(),
            'cvlan'               => $this->cvlan,
            'gpon_attestazione'   => $this->gpon_attestazione,
            'retry_count'         => $this->retry_count,
            'next_retry_at'       => $this->next_retry_at?->toIso8601String(),
            'last_error'          => $this->last_error,
            'sent_at'             => $this->sent_at?->toIso8601String(),
            'completed_at'        => $this->completed_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
