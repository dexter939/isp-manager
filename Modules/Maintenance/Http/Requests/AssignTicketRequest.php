<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'note'    => ['nullable', 'string'],
        ];
    }
}
