<?php

namespace Modules\Maintenance\RouteOptimizer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OptimizeRouteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'technician_id' => 'required|uuid',
            'date'          => 'required|date',
        ];
    }
}
