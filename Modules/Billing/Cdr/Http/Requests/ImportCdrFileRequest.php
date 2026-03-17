<?php

namespace Modules\Billing\Cdr\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCdrFileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file'   => 'required|file|mimes:csv,txt',
            'format' => 'sometimes|in:asterisk,yeastar,generic',
        ];
    }
}
