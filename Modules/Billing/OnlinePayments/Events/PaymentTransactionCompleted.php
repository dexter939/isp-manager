<?php

namespace Modules\Billing\OnlinePayments\Events;

use Modules\Billing\OnlinePayments\Models\OnlinePaymentTransaction;

class PaymentTransactionCompleted
{
    public function __construct(
        public readonly OnlinePaymentTransaction $transaction,
    ) {}
}
