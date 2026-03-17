<?php

namespace Modules\Billing\CustomerBalance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetOpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount_cents' => 'required|integer',
            'date'         => 'required|date',
            'note'         => 'required|string|max:500',
        ];
    }
}
