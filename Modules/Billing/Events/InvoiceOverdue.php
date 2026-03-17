<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Invoice;

class InvoiceOverdue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $daysOverdue,
    ) {}
}
