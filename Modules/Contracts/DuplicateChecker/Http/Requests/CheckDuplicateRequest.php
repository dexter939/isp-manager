<?php

namespace Modules\Contracts\DuplicateChecker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckDuplicateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'      => 'nullable|email',
            'phone'      => 'nullable|string',
            'exclude_id' => 'nullable|uuid',
        ];
    }
}
