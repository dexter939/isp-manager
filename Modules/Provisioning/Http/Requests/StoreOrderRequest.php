<?php

declare(strict_types=1);

namespace Modules\Provisioning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'order_type'  => ['required', 'in:activation,change,deactivation,migration'],
        ];
    }
}
