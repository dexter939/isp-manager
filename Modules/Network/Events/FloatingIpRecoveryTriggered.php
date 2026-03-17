<?php

declare(strict_types=1);

namespace Modules\Network\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Network\Models\FloatingIpPair;

class FloatingIpRecoveryTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly FloatingIpPair $pair,
        public readonly string $reason,
    ) {}
}
