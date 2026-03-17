<?php

namespace Modules\Billing\CustomerBalance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount_cents' => 'required|integer',
            'description'  => 'required|string|max:500',
        ];
    }
}
