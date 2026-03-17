<?php

namespace Modules\Infrastructure\NetworkSites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkHardwareRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hardware_id'      => 'required|uuid',
            'is_access_device' => 'boolean',
        ];
    }
}
