<?php

namespace Modules\Billing\Bundles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBundleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string',
            'price_amount'                => 'required|integer|min:0',
            'billing_period'              => 'required|in:monthly,bimonthly,quarterly,semiannual,annual',
            'is_active'                   => 'boolean',
            'items'                       => 'required|array|min:1',
            'items.*.service_type'        => 'required|in:internet,voip,static_ip,iptv,other',
            'items.*.description'         => 'required|string',
            'items.*.list_price_amount'   => 'required|integer|min:0',
            'items.*.service_id'          => 'nullable|uuid',
            'items.*.sort_order'          => 'integer',
        ];
    }
}
