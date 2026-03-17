<?php

declare(strict_types=1);

namespace Modules\Provisioning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
