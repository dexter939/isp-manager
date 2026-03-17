<?php

namespace Modules\Billing\PaymentMatching\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulateMatchingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'payment_data' => 'required|array',
        ];
    }
}
