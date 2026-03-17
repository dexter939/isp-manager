<?php

declare(strict_types=1);

namespace Modules\Provisioning\Data;

use Spatie\LaravelData\Data;

class TroubleTicketRequest extends Data
{
    public function __construct(
        public readonly string  $codiceOrdineOlo,
        public readonly string  $codiceOrdineOf,
        public readonly string  $recapitoTelefonicoCliente,  // OBBLIGATORIO spec OF
        public readonly string  $causaGuasto,                // es: "01" = Causa Open Fiber
        public readonly string  $descTecnicaGuasto,          // es: "10" = Sostituzione Apparati
        public readonly ?string $ticketId    = null,         // per update/close
        public readonly ?string $noteAgente  = null,
    ) {}
}
