<?php

namespace Modules\Contracts\AgentNetwork\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'agent_id'    => $this->agent_id,
            'contract_id' => $this->contract_id,
            'amount'      => [
                'amount'    => $this->amount_cents,
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($this->amount_cents / 100, 2, ',', '.'),
            ],
            'type'        => $this->type ?? null,
            'status'      => $this->status,
            'period'      => $this->period_month?->format('Y-m'),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
