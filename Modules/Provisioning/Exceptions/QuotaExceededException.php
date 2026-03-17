<?php

declare(strict_types=1);

namespace Modules\Provisioning\Exceptions;

use App\Exceptions\ApiException;

class QuotaExceededException extends ApiException
{
    public function __construct(string $carrier, string $callType)
    {
        parent::__construct(
            "Quota giornaliera esaurita per carrier={$carrier} tipo={$callType}. Riprovare domani.",
            429,
        );
    }
}
