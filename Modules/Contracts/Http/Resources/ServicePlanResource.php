<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Resources;

use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $priceMonthly   = $this->price_monthly !== null
            ? Money::of((string) $this->price_monthly, 'EUR')
            : null;

        $activationFee  = $this->activation_fee !== null
            ? Money::of((string) $this->activation_fee, 'EUR')
            : null;

        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'carrier'              => $this->carrier?->value,
            'technology'           => $this->technology,
            'price_monthly'        => $priceMonthly ? [
                'amount'    => $priceMonthly->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($priceMonthly->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'activation_fee'       => $activationFee ? [
                'amount'    => $activationFee->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($activationFee->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'bandwidth_dl'         => $this->bandwidth_dl,
            'bandwidth_ul'         => $this->bandwidth_ul,
            'sla_type'             => $this->sla_type,
            'min_contract_months'  => $this->min_contract_months,
            'is_active'            => $this->is_active,
            'is_public'            => $this->is_public,
            'description'          => $this->description,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
