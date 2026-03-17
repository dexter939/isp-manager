<?php

namespace Modules\AI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'to'          => ['required', 'string'],
            'body'        => ['required', 'string', 'max:4096'],
            'customer_id' => ['nullable', 'integer'],
        ];
    }
}
