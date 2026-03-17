<?php

declare(strict_types=1);

namespace Modules\Provisioning\Data;

use Modules\Provisioning\Enums\OrderState;
use Spatie\LaravelData\Data;

class WebhookResult extends Data
{
    public function __construct(
        public readonly bool        $parsed,
        public readonly string      $messageType,       // OF_StatusUpdate, OF_CompletionOrder, ecc.
        public readonly ?string     $codiceOrdineOlo,
        public readonly ?string     $codiceOrdineOf,
        public readonly ?OrderState $newState,
        public readonly ?string     $scheduledDate,
        public readonly ?string     $cvlan,
        public readonly ?string     $gponAttestazione,  // max 30 char
        public readonly ?string     $idApparatoConsegnato,
        public readonly ?string     $flagDesospensione,
        public readonly array       $rawFields = [],    // tutti i campi raw per audit
        public readonly ?string     $errorMessage = null,
    ) {}
}
