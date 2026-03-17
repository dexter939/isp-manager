<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $amount = $this->amount !== null
            ? Money::of((string) $this->amount, $this->currency ?? 'EUR')
            : null;

        return [
            'id'           => $this->id,
            'invoice_id'   => $this->invoice_id,
            'customer_id'  => $this->customer_id,
            'method'       => $this->method,
            'amount'       => $amount ? [
                'amount'    => $amount->getMinorAmount()->toInt(),
                'currency'  => $amount->getCurrency()->getCurrencyCode(),
                'formatted' => '€' . number_format($amount->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'status'        => $this->status?->value,
            'sepa_end_to_end_id' => $this->sepa_end_to_end_id,
            'sepa_return_code'   => $this->sepa_return_code,
            'processed_at'  => $this->processed_at?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
