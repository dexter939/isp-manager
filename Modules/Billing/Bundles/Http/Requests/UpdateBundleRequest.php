<?php

namespace Modules\Billing\Bundles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBundleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => 'string|max:255',
            'price_amount'   => 'integer|min:0',
            'billing_period' => 'in:monthly,bimonthly,quarterly,semiannual,annual',
            'is_active'      => 'boolean',
        ];
    }
}
