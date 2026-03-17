<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateStripeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'payment_method_id' => 'required|string',
        ];
    }
}
