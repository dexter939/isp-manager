<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'type'                   => $this->type?->value,
            'ragione_sociale'        => $this->ragione_sociale,
            'nome'                   => $this->nome,
            'cognome'                => $this->cognome,
            'full_name'              => $this->full_name,
            // codice_fiscale is encrypted at rest — omitted from API output
            'pec'                    => $this->pec,
            'email'                  => $this->email,
            'telefono'               => $this->telefono,
            'cellulare'              => $this->cellulare,
            'indirizzo_fatturazione' => $this->indirizzo_fatturazione,
            'payment_method'         => $this->payment_method?->value,
            'status'                 => $this->status?->value,
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),

            // Relationships — only included when eager-loaded
            'contracts'              => ContractResource::collection($this->whenLoaded('contracts')),
        ];
    }
}
