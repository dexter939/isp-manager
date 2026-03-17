<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveInventoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity'  => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string'],
        ];
    }
}
