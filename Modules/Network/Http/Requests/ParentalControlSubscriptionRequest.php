<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParentalControlSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id'      => ['required', 'uuid'],
            'pppoe_account_id' => ['nullable', 'uuid'],
            'profile_id'       => ['required', 'uuid'],
        ];
    }
}
