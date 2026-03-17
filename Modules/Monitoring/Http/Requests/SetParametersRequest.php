<?php

namespace Modules\Monitoring\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetParametersRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'parameters'   => ['required', 'array', 'min:1'],
            'parameters.*' => ['string', 'max:255'],
        ];
    }
}
