<?php

namespace Modules\Billing\PosteItaliane\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBollettinoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
        ];
    }
}
