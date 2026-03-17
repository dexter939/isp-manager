<?php

namespace Modules\Maintenance\FieldService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddMaterialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description'   => 'required|string',
            'quantity'      => 'integer|min:1',
            'serial_number' => 'nullable|string',
        ];
    }
}
