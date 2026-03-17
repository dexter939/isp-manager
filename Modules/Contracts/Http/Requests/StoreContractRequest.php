<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Contracts\Enums\BillingCycle;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Contracts\Models\Contract::class);
    }

    public function rules(): array
    {
        return [
            'customer_id'      => ['required', 'integer', 'exists:customers,id'],
            'service_plan_id'  => ['required', 'integer', 'exists:service_plans,id'],

            'indirizzo_installazione'            => ['required', 'array'],
            'indirizzo_installazione.via'        => ['required', 'string', 'max:200'],
            'indirizzo_installazione.civico'     => ['required', 'string', 'max:10'],
            'indirizzo_installazione.comune'     => ['required', 'string', 'max:100'],
            'indirizzo_installazione.provincia'  => ['required', 'string', 'size:2'],
            'indirizzo_installazione.cap'        => ['required', 'string', 'size:5'],
            'indirizzo_installazione.scala'      => ['nullable', 'string', 'max:10'],
            'indirizzo_installazione.piano'      => ['nullable', 'string', 'max:10'],
            'indirizzo_installazione.interno'    => ['nullable', 'string', 'max:10'],

            'codice_ui'     => ['nullable', 'string', 'max:20'],
            'id_building'   => ['nullable', 'string', 'max:50'],

            'billing_cycle' => ['required', 'in:' . implode(',', array_column(BillingCycle::cases(), 'value'))],
            'billing_day'   => ['required', 'integer', 'min:1', 'max:28'],

            'activation_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
