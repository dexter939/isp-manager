<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FloatingIpRequest extends FormRequest
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
            'name'                      => ['required', 'string', 'max:255'],
            'master_pppoe_account_id'   => ['required', 'uuid'],
            'failover_pppoe_account_id' => [
                'required',
                'uuid',
                Rule::notIn([$this->input('master_pppoe_account_id')]),
            ],
            'resources'                 => ['nullable', 'array'],
            'resources.*.resource_type' => [
                'required',
                Rule::in(['ipv4', 'ipv4_subnet', 'ipv6_prefix']),
            ],
            'resources.*.resource_value' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'failover_pppoe_account_id.not_in' =>
                'The failover account must be different from the master account.',
        ];
    }
}
