<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\Payment;

class PaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly Invoice $invoice,
    ) {}
}
