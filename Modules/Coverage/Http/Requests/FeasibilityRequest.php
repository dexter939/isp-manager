<?php

declare(strict_types=1);

namespace Modules\Coverage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeasibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('coverage.view');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'via'       => ['required', 'string', 'min:3', 'max:200'],
            'civico'    => ['required', 'string', 'max:10'],
            'comune'    => ['required', 'string', 'min:2', 'max:100'],
            'provincia' => ['required', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'via.required'       => 'Il campo via è obbligatorio.',
            'civico.required'    => 'Il numero civico è obbligatorio.',
            'comune.required'    => 'Il comune è obbligatorio.',
            'provincia.required' => 'La provincia è obbligatoria.',
            'provincia.size'     => 'La provincia deve essere la sigla a 2 lettere (es: BA, MI, NA).',
        ];
    }
}
