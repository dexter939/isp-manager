<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Contracts\Enums\CustomerType;
use Modules\Contracts\Enums\PaymentMethod;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Contracts\Models\Customer::class);
    }

    public function rules(): array
    {
        $type = $this->input('type', 'privato');

        return [
            'type'             => ['required', 'in:privato,azienda'],
            'ragione_sociale'  => ['required_if:type,azienda', 'nullable', 'string', 'max:200'],
            'nome'             => ['required_if:type,privato', 'nullable', 'string', 'max:100'],
            'cognome'          => ['required_if:type,privato', 'nullable', 'string', 'max:100'],
            'codice_fiscale'   => ['nullable', 'string', 'min:11', 'max:16'],
            'piva'             => ['required_if:type,azienda', 'nullable', 'string', 'size:11'],
            'email'            => ['required', 'email:rfc,dns', 'max:255'],
            'pec'              => ['nullable', 'email:rfc', 'max:255'],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'cellulare'        => ['required', 'string', 'max:20'],
            'payment_method'   => ['required', 'in:' . implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'iban'             => ['required_if:payment_method,sdd', 'nullable', 'string', 'max:34'],

            'indirizzo_fatturazione'          => ['required', 'array'],
            'indirizzo_fatturazione.via'      => ['required', 'string', 'max:200'],
            'indirizzo_fatturazione.civico'   => ['required', 'string', 'max:10'],
            'indirizzo_fatturazione.comune'   => ['required', 'string', 'max:100'],
            'indirizzo_fatturazione.provincia'=> ['required', 'string', 'size:2'],
            'indirizzo_fatturazione.cap'      => ['required', 'string', 'size:5'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ragione_sociale.required_if' => 'La ragione sociale è obbligatoria per le aziende.',
            'nome.required_if'            => 'Il nome è obbligatorio per le persone fisiche.',
            'cognome.required_if'         => 'Il cognome è obbligatorio per le persone fisiche.',
            'piva.required_if'            => 'La Partita IVA è obbligatoria per le aziende.',
            'iban.required_if'            => 'L\'IBAN è obbligatorio per il pagamento con addebito SEPA.',
            'cellulare.required'          => 'Il cellulare è obbligatorio per la firma via OTP.',
        ];
    }
}
