<?php

declare(strict_types=1);

namespace Modules\Provisioning\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Models\CarrierOrder;

class OrderStateChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CarrierOrder $order,
        public readonly OrderState   $from,
        public readonly OrderState   $to,
        public readonly array        $context = [],
    ) {}
}
