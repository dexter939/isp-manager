<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Resources;

use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Contracts\Http\Resources\CustomerResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total     = $this->total !== null
            ? Money::of((string) $this->total, 'EUR')
            : null;

        $subtotal  = $this->subtotal !== null
            ? Money::of((string) $this->subtotal, 'EUR')
            : null;

        $taxAmount = $this->tax_amount !== null
            ? Money::of((string) $this->tax_amount, 'EUR')
            : null;

        $stampDuty = $this->stamp_duty !== null
            ? Money::of((string) $this->stamp_duty, 'EUR')
            : null;

        return [
            'id'               => $this->id,
            'number'           => $this->number,
            'customer_id'      => $this->customer_id,
            'contract_id'      => $this->contract_id,
            'agent_id'         => $this->agent_id,
            'type'             => $this->type?->value,
            'status'           => $this->status?->value,
            'period_from'      => $this->period_from?->toIso8601String(),
            'period_to'        => $this->period_to?->toIso8601String(),
            'issue_date'       => $this->issue_date?->toIso8601String(),
            'due_date'         => $this->due_date?->toIso8601String(),
            'tax_rate'         => $this->tax_rate,
            'subtotal'         => $subtotal ? [
                'amount'    => $subtotal->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($subtotal->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'tax_amount'       => $taxAmount ? [
                'amount'    => $taxAmount->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($taxAmount->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'stamp_duty'       => $stampDuty ? [
                'amount'    => $stampDuty->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($stampDuty->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'total'            => $total ? [
                'amount'    => $total->getMinorAmount()->toInt(),
                'currency'  => 'EUR',
                'formatted' => '€' . number_format($total->getAmount()->toFloat(), 2, '.', ''),
            ] : null,
            'payment_method'   => $this->payment_method,
            'paid_at'          => $this->paid_at?->toIso8601String(),
            'sdi_status'       => $this->sdi_status,
            'sdi_message_id'   => $this->sdi_message_id,
            'sdi_filename'     => $this->sdi_filename,
            'sdi_sent_at'      => $this->sdi_sent_at?->toIso8601String(),
            'sdi_acknowledged_at' => $this->sdi_acknowledged_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),

            // Relationships — only included when eager-loaded
            'customer'         => new CustomerResource($this->whenLoaded('customer')),
            'lines'            => $this->whenLoaded('items', fn() => $this->items),
        ];
    }
}
