<?php

namespace Modules\Billing\DunningManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:100',
            'steps'      => 'required|array',
            'is_default' => 'boolean',
            'active'     => 'boolean',
        ];
    }
}
