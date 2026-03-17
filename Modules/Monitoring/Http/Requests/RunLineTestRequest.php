<?php

namespace Modules\Monitoring\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunLineTestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'carrier' => ['required', 'string', 'in:openfiber,fibercop'],
        ];
    }
}
