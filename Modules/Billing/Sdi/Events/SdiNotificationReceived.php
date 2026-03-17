<?php

namespace Modules\Billing\Sdi\Events;

use Modules\Billing\Sdi\Models\SdiNotification;
use Modules\Billing\Sdi\Models\SdiTransmission;

class SdiNotificationReceived
{
    public function __construct(
        public readonly SdiTransmission $transmission,
        public readonly SdiNotification $notification,
    ) {}
}
