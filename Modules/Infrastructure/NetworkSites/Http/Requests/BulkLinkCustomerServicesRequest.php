<?php

namespace Modules\Infrastructure\NetworkSites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkLinkCustomerServicesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hardware_id'    => 'required|uuid',
            'contract_ids'   => 'required|array|min:1',
            'contract_ids.*' => 'uuid',
        ];
    }
}
