<?php

namespace Modules\Infrastructure\Topology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'link_type'        => 'in:fiber,radio,copper,uplink,aggregate,other',
            'bandwidth_mbps'   => 'nullable|integer',
            'source_interface' => 'nullable|string',
            'target_interface' => 'nullable|string',
            'description'      => 'nullable|string',
            'is_monitored'     => 'boolean',
            'status'           => 'in:up,down,degraded,unknown',
        ];
    }
}
