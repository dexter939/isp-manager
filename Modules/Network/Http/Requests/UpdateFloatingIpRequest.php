<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFloatingIpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
