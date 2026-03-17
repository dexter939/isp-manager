<?php

declare(strict_types=1);

namespace Modules\Monitoring\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Monitoring\Models\NetworkAlert;

class NetworkAlertCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly NetworkAlert $alert,
    ) {}
}
