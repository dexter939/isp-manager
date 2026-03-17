<?php

namespace Modules\Billing\PaymentMatching\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchingRuleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'string|max:255',
            'priority'    => 'integer|min:1',
            'criteria'    => 'array',
            'action'      => 'in:match_oldest,match_newest,add_to_credit,skip',
            'action_note' => 'nullable|string',
            'is_active'   => 'boolean',
        ];
    }
}
