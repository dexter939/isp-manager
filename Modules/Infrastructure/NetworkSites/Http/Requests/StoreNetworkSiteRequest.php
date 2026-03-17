<?php

namespace Modules\Infrastructure\NetworkSites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNetworkSiteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:pop,cabinet,datacenter,mast,building,other',
            'address'         => 'nullable|string',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
            'altitude_meters' => 'nullable|integer',
            'description'     => 'nullable|string',
            'contact_name'    => 'nullable|string',
            'contact_phone'   => 'nullable|string',
            'contact_email'   => 'nullable|email',
            'lessor_name'     => 'nullable|string',
            'lease_expiry'    => 'nullable|date',
            'status'          => 'in:active,maintenance,decommissioned',
            'importance'      => 'in:critical,high,normal,low',
        ];
    }
}
