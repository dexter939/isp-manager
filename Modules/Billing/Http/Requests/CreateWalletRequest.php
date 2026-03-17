<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'                  => ['required', 'uuid'],
            'low_balance_threshold_amount' => ['nullable', 'integer', 'min:0'],
            'auto_suspend_on_zero'         => ['nullable', 'boolean'],
        ];
    }
}
