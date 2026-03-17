<?php

namespace Modules\Billing\DunningManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePolicyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => 'sometimes|string|max:100',
            'steps'      => 'sometimes|array',
            'is_default' => 'boolean',
            'active'     => 'boolean',
        ];
    }
}
