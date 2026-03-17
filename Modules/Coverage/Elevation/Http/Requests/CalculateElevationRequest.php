<?php

namespace Modules\Coverage\Elevation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateElevationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'network_site_id'  => 'required|uuid',
            'customer_lat'     => 'required|numeric|between:-90,90',
            'customer_lon'     => 'required|numeric|between:-180,180',
            'customer_address' => 'nullable|string',
            'antenna_height_m' => 'integer|min:1|max:200',
            'cpe_height_m'     => 'integer|min:1|max:50',
            'frequency_ghz'    => 'nullable|numeric|min:0.1|max:100',
        ];
    }
}
