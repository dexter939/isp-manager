<?php

namespace Modules\Maintenance\FieldService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddActivityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description'      => 'required|string',
            'duration_minutes' => 'nullable|integer',
        ];
    }
}
