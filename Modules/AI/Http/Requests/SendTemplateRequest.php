<?php

namespace Modules\AI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'to'            => ['required', 'string'],
            'template_name' => ['required', 'string'],
            'language_code' => ['string', 'max:10'],
            'components'    => ['array'],
        ];
    }
}
