<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string'],
            'note'   => ['nullable', 'string'],
        ];
    }
}
