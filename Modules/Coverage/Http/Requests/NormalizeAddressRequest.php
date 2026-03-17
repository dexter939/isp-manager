<?php

declare(strict_types=1);

namespace Modules\Coverage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NormalizeAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Endpoint pubblico (usato dal wizard contratti)
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'via'       => ['required', 'string', 'max:200'],
            'civico'    => ['required', 'string', 'max:10'],
            'comune'    => ['sometimes', 'string', 'max:100'],
            'provincia' => ['sometimes', 'string', 'max:5'],
        ];
    }
}
