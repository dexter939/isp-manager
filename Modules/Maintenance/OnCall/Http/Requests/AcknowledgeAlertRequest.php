<?php

namespace Modules\Maintenance\OnCall\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeAlertRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'dispatch_id' => 'required|uuid',
        ];
    }
}
