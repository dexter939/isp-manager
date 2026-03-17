<?php

namespace Modules\Infrastructure\TopologyDiscovery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkConfirmRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'candidate_ids'   => 'required|array|min:1',
            'candidate_ids.*' => 'uuid',
        ];
    }
}
