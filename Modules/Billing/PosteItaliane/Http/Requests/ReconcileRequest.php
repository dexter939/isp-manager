<?php

namespace Modules\Billing\PosteItaliane\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReconcileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt',
        ];
    }
}
