<?php

namespace Modules\Billing\OnlinePayments\Events;

use Modules\Billing\OnlinePayments\Models\OnlinePaymentMethod;

class PaymentMethodAdded
{
    public function __construct(
        public readonly OnlinePaymentMethod $method,
    ) {}
}
