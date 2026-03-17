<?php

namespace Modules\Network\FairUsage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FupTopupProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'extra_gb'   => $this->gb_amount,
            'price'      => [
                'amount'    => $this->price_amount,
                'currency'  => $this->price_currency,
                'formatted' => '€' . number_format($this->price_amount / 100, 2, ',', '.'),
            ],
            'active'     => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
