<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParentalControlRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'blacklist'   => ['nullable', 'array'],
            'blacklist.*' => ['string'],
            'whitelist'   => ['nullable', 'array'],
            'whitelist.*' => ['string'],
        ];
    }
}
