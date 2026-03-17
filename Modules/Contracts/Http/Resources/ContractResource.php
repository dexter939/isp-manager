<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Resources;

use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $monthlyPrice  = $this->monthly_price !== null
            ? Money::of((string) $this->monthly_price, 'EUR')
            : null;

        $activationFee = $this->activation_fee !== null
            ? Money::of((string) $this->activation_fee, 'EUR')
            : null;

        $modemFee      = $this->modem_fee !== null
            ? Money::of((string) $this->modem_fee, 'EUR')
            : null;

        return [
            'id'                      => $this->id,
            'customer_id'             => $this->customer_id,
            'service_plan_id'         => $this->service_plan_id,
            'carrier'                 => $this->carrier?->value,
            'billing_cycle'           => $this->billing_cycle?->value,
            'billing_day'             => $this->billing_day,
            'monthly_price'           => $monthlyPrice ? [
                'amount'    => $monthlyPrice->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($monthlyPrice->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'activation_fee'          => $activationFee ? [
                'amount'    => $activationFee->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($activationFee->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'modem_fee'               => $modemFee ? [
                'amount'    => $modemFee->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($modemFee->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'status'                  => $this->status?->value,
            'activation_date'         => $this->activation_date?->toIso8601String(),
            'termination_date'        => $this->termination_date?->toIso8601String(),
            'min_end_date'            => $this->min_end_date?->toIso8601String(),
            'signed_at'               => $this->signed_at?->toIso8601String(),
            'indirizzo_installazione' => $this->indirizzo_installazione,
            'codice_ui'               => $this->codice_ui,
            'id_building'             => $this->id_building,
            'agent_id'                => $this->agent_id,
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),

            // Relationships — only included when eager-loaded
            'customer'                => new CustomerResource($this->whenLoaded('customer')),
            'service_plan'            => new ServicePlanResource($this->whenLoaded('servicePlan')),
        ];
    }
}
