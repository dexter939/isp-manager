<?php

namespace Modules\Maintenance\Dispatcher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'scheduled_start'            => 'required|date',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'travel_time_minutes'        => 'nullable|integer|min:0',
        ];
    }
}
