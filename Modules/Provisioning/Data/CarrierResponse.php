<?php

declare(strict_types=1);

namespace Modules\Provisioning\Data;

use Spatie\LaravelData\Data;

class CarrierResponse extends Data
{
    public function __construct(
        public readonly bool    $success,
        public readonly string  $carrierId,       // ID assegnato dal carrier (codice_ordine_of)
        public readonly ?string $rawPayload,      // XML/JSON raw ricevuto
        public readonly int     $httpStatus,
        public readonly ?string $errorCode    = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public function isAck(): bool
    {
        return $this->success;
    }

    public function isNack(): bool
    {
        return !$this->success;
    }
}
