<?php

namespace Modules\Infrastructure\NetworkSites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNetworkSiteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => 'string|max:255',
            'type'         => 'in:pop,cabinet,datacenter,mast,building,other',
            'address'      => 'nullable|string',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
            'status'       => 'in:active,maintenance,decommissioned',
            'importance'   => 'in:critical,high,normal,low',
            'lease_expiry' => 'nullable|date',
        ];
    }
}
