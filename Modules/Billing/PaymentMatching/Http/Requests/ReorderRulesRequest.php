<?php

namespace Modules\Billing\PaymentMatching\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRulesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'rules'             => 'required|array',
            'rules.*.id'        => 'required|uuid',
            'rules.*.priority'  => 'required|integer|min:1',
        ];
    }
}
