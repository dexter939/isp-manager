<?php

namespace Modules\Maintenance\PurchaseOrders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReorderRuleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'inventory_model_id' => 'required|uuid',
            'supplier_id'        => 'required|uuid',
            'min_stock_quantity' => 'required|integer|min:0',
            'reorder_quantity'   => 'required|integer|min:1',
            'auto_order'         => 'boolean',
        ];
    }
}
