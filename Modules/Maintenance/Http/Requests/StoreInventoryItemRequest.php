<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sku'               => ['required', 'string', 'max:100', 'unique:inventory_items,sku'],
            'name'              => ['required', 'string', 'max:255'],
            'category'          => ['nullable', 'string', 'in:ont,router,cable,splitter,other'],
            'unit'              => ['string', 'in:pcs,mt,kg'],
            'reorder_threshold' => ['integer', 'min:0'],
            'unit_cost'         => ['nullable', 'numeric', 'min:0'],
            'supplier'          => ['nullable', 'string', 'max:100'],
            'location'          => ['nullable', 'string', 'max:100'],
        ];
    }
}
