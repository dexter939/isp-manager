<?php

namespace Modules\Billing\DunningManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDunningPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'steps'         => ['required', 'array', 'min:1'],
            'steps.*.day'   => ['required', 'integer', 'min:1'],
            'steps.*.action'=> ['required', 'string', 'in:email,sms,whatsapp,suspend,terminate'],
            'is_default'    => ['boolean'],
            'active'        => ['boolean'],
        ];
    }
}
