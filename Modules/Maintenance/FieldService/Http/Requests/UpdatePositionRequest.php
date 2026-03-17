<?php

namespace Modules\Maintenance\FieldService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lat'      => 'required|numeric|between:-90,90',
            'lon'      => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|integer',
        ];
    }
}
