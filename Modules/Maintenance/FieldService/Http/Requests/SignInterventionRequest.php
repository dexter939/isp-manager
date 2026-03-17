<?php

namespace Modules\Maintenance\FieldService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignInterventionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'otp'              => 'required|string|size:6',
            'signature_base64' => 'required|string',
            'signer_name'      => 'required|string',
        ];
    }
}
