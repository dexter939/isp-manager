<?php

namespace Modules\Billing\OnlinePayments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
        ];
    }
}
