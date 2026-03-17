<?php

namespace Modules\Maintenance\InventoryRma\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeployItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'   => 'required|uuid',
            'contract_id'   => 'required|uuid',
            'technician_id' => 'required|uuid',
        ];
    }
}
