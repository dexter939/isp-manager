<?php

namespace Modules\Infrastructure\Topology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'source_device_id'  => 'required|uuid',
            'target_device_id'  => 'required|uuid',
            'network_site_id'   => 'nullable|uuid',
            'link_type'         => 'required|in:fiber,radio,copper,uplink,aggregate,other',
            'bandwidth_mbps'    => 'nullable|integer|min:1',
            'source_interface'  => 'nullable|string',
            'target_interface'  => 'nullable|string',
            'description'       => 'nullable|string',
            'is_monitored'      => 'boolean',
        ];
    }
}
