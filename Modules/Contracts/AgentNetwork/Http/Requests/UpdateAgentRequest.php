<?php

namespace Modules\Contracts\AgentNetwork\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'business_name'   => 'sometimes|string|max:100',
            'iban'            => 'sometimes|string|max:34',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
            'status'          => 'sometimes|in:active,suspended,terminated',
        ];
    }
}
