<?php

namespace Modules\Billing\Sdi\Events;

use Modules\Billing\Sdi\Models\SdiTransmission;

class SdiTransmissionCreated
{
    public function __construct(
        public readonly SdiTransmission $transmission,
    ) {}
}
