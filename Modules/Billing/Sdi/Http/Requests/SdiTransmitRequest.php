<?php

namespace Modules\Billing\Sdi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SdiTransmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'billing']) ?? false;
    }

    public function rules(): array
    {
        return [
            'channel' => ['sometimes', 'string', 'in:aruba,pec'],
        ];
    }
}
