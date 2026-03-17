<?php

namespace Modules\Billing\DunningManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToWhitelistRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:users,id',
            'reason'      => 'required|string',
            'expires_at'  => 'nullable|date',
        ];
    }
}
