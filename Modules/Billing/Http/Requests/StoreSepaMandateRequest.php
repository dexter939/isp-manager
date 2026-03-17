<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSepaMandateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'iban'           => ['required', 'string', 'max:34', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/'],
            'bic'            => ['nullable', 'string', 'max:11', 'regex:/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/'],
            'account_holder' => ['required', 'string', 'max:140'],
            'signed_at'      => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'iban.regex'      => 'L\'IBAN non è in formato valido.',
            'bic.regex'       => 'Il BIC/SWIFT non è in formato valido.',
            'signed_at.before_or_equal' => 'La data di firma non può essere nel futuro.',
        ];
    }
}
