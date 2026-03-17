<?php

namespace Modules\Maintenance\FieldService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartInterventionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ];
    }
}
