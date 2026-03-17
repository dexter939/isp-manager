<?php

namespace Modules\Maintenance\OnCall\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string',
            'escalation_timeout_minutes'  => 'integer|min:1|max:1440',
        ];
    }
}
