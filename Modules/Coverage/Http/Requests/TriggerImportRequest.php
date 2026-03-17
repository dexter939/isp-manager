<?php

namespace Modules\Coverage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TriggerImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'carrier'   => ['required', 'in:fibercop,openfiber'],
            'file_path' => ['required', 'string'],
        ];
    }
}
