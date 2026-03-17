<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepaidTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id'        => ['required', 'uuid'],
            'product_id'       => ['required', 'uuid'],
            'payment_method'   => ['required', 'in:paypal,bank_transfer,reseller,admin'],
            'payment_reference' => ['nullable', 'string'],
            'reseller_id'      => [
                'nullable',
                'uuid',
                'required_if:payment_method,reseller',
            ],
        ];
    }
}
