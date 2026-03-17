<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Contracts\Http\Resources\CustomerResource;

class TroubleTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'ticket_number'      => $this->ticket_number,
            'customer_id'        => $this->customer_id,
            'contract_id'        => $this->contract_id,
            'assigned_to'        => $this->assigned_to,
            'title'              => $this->title,
            'description'        => $this->description,
            'status'             => $this->status?->value,
            'priority'           => $this->priority?->value,
            'type'               => $this->type,
            'source'             => $this->source,
            'carrier'            => $this->carrier,
            'carrier_ticket_id'  => $this->carrier_ticket_id,
            'opened_at'          => $this->opened_at?->toIso8601String(),
            'first_response_at'  => $this->first_response_at?->toIso8601String(),
            'due_at'             => $this->due_at?->toIso8601String(),
            'resolved_at'        => $this->resolved_at?->toIso8601String(),
            'closed_at'          => $this->closed_at?->toIso8601String(),
            'resolution_notes'   => $this->resolution_notes,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),

            // Relationships — only included when eager-loaded
            'customer'           => new CustomerResource($this->whenLoaded('customer')),
            'notes'              => $this->whenLoaded('notes', fn() => $this->notes),
        ];
    }
}
