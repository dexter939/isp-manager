<?php

namespace Modules\Maintenance\InventoryRma\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenRmaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason'      => 'required|in:defective,warranty,wrong_item,other',
            'description' => 'required|string',
            'supplier_id' => 'nullable|uuid',
        ];
    }
}
