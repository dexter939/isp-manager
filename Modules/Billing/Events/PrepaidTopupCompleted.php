<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Modules\Billing\Models\PrepaidTopupOrder;
use Modules\Billing\Models\PrepaidTransaction;

class PrepaidTopupCompleted
{
    public function __construct(
        public readonly PrepaidTopupOrder  $order,
        public readonly PrepaidTransaction $transaction,
    ) {}
}
