<?php

namespace Modules\Maintenance\InventoryRma\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveRmaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'resolution'           => 'required|in:replaced,repaired,credit,rejected',
            'replacement_item_id'  => 'nullable|uuid',
        ];
    }
}
