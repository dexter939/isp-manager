<?php

namespace Modules\Contracts\AgentNetwork\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'         => 'required|exists:users,id|unique:agents,user_id',
            'business_name'   => 'required|string|max:100',
            'piva'            => 'nullable|string|size:11',
            'codice_fiscale'  => 'required|string|max:16',
            'iban'            => 'required|string|max:34',
            'commission_rate' => 'numeric|min:0|max:100',
        ];
    }
}
