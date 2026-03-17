<?php

namespace Modules\Maintenance\PurchaseOrders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'                        => 'required|uuid',
            'reference_number'                   => 'nullable|string',
            'notes'                              => 'nullable|string',
            'items'                              => 'required|array|min:1',
            'items.*.inventory_model_id'         => 'required|uuid',
            'items.*.quantity_ordered'           => 'required|integer|min:1',
        ];
    }
}
