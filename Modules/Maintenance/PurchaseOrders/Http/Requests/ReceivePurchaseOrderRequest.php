<?php

namespace Modules\Maintenance\PurchaseOrders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'       => 'required|array',
            'items.*.id'  => 'required|uuid',
            'items.*.qty' => 'required|integer|min:1',
        ];
    }
}
