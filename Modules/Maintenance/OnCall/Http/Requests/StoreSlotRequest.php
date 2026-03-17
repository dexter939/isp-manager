<?php

namespace Modules\Maintenance\OnCall\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSlotRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'        => 'required|uuid',
            'level'          => 'integer|min:1|max:5',
            'start_datetime' => 'required|date',
            'end_datetime'   => 'required|date|after:start_datetime',
            'repeat_rule'    => 'nullable|string',
        ];
    }
}
