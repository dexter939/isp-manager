<?php

declare(strict_types=1);

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterHardwareAssetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'             => ['required', 'string', 'in:ont,router,cpe_fwa,sim,other'],
            'serial_number'    => ['required', 'string', 'max:100', 'unique:hardware_assets,serial_number'],
            'mac_address'      => ['nullable', 'string', 'max:17'],
            'qr_code'          => ['nullable', 'string', 'max:100'],
            'brand'            => ['nullable', 'string', 'max:100'],
            'model'            => ['nullable', 'string', 'max:100'],
            'purchase_price'   => ['nullable', 'numeric', 'min:0'],
            'purchase_date'    => ['nullable', 'date'],
            'warranty_expires' => ['nullable', 'date'],
            'supplier'         => ['nullable', 'string', 'max:150'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
