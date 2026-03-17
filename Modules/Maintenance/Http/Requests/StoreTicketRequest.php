<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority'    => ['required', 'string', 'in:low,medium,high,critical'],
            'type'        => ['nullable', 'string', 'in:assurance,billing,provisioning,other'],
            'customer_id' => ['nullable', 'integer'],
            'contract_id' => ['nullable', 'integer'],
        ];
    }
}
