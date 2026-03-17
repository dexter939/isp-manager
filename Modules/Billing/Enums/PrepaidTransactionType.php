<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PrepaidTransactionType: string
{
    case Topup      = 'topup';
    case Charge     = 'charge';
    case Refund     = 'refund';
    case Commission = 'commission';
    case Adjustment = 'adjustment';
}
