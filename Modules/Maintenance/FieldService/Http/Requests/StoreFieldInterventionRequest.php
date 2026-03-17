<?php

namespace Modules\Maintenance\FieldService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFieldInterventionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'       => 'required|exists:users,id',
            'intervention_type' => 'required|in:installation,repair,maintenance,inspection,removal',
            'scheduled_at'      => 'required|date',
            'address'           => 'required|string',
            'technician_id'     => 'nullable|exists:users,id',
            'contract_id'       => 'nullable|exists:contracts,id',
            'notes'             => 'nullable|string',
        ];
    }
}
