<?php

declare(strict_types=1);

namespace Modules\Network\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Network\Models\ParentalControlSubscription;

class ParentalControlActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ParentalControlSubscription $subscription,
    ) {}
}
