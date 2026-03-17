<?php

namespace Modules\AI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DraftTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'text'        => ['required', 'string', 'max:2000'],
            'customer_id' => ['nullable', 'integer'],
            'contract_id' => ['nullable', 'integer'],
        ];
    }
}
