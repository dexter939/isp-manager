<?php

namespace Modules\Billing\Cdr\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportAnagrafeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'year' => 'required|integer|min:2020|max:' . date('Y'),
        ];
    }
}
