<?php

namespace Modules\Maintenance\Dispatcher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'intervention_id'            => 'required|uuid',
            'technician_id'              => 'required|uuid',
            'scheduled_start'            => 'required|date',
            'estimated_duration_minutes' => 'required|integer|min:1',
            'travel_time_minutes'        => 'integer|min:0',
        ];
    }
}
