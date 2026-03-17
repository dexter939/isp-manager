<?php

namespace Modules\Billing\Bundles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeBundleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'contract_id'         => 'required|uuid',
            'bundle_plan_id'      => 'required|uuid',
            'custom_price_amount' => 'nullable|integer|min:0',
        ];
    }
}
