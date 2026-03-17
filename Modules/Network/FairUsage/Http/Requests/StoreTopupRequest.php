<?php

namespace Modules\Network\FairUsage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopupRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pppoe_account_id' => 'required|uuid',
            'product_id'       => 'required|uuid',
            'payment_method'   => 'required|string',
        ];
    }
}
