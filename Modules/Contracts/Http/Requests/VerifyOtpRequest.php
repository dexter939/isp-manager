<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Il link OTP è pubblico (firmato con URL temporaneo)
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'Il codice OTP è obbligatorio.',
            'otp.size'     => 'Il codice OTP deve essere di 6 cifre.',
            'otp.regex'    => 'Il codice OTP deve contenere solo cifre.',
        ];
    }
}
