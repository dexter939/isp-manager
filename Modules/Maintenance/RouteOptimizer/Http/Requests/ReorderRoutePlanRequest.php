<?php

namespace Modules\Maintenance\RouteOptimizer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRoutePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'optimized_order' => 'required|array',
        ];
    }
}
