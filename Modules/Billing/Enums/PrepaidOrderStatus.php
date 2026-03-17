<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PrepaidOrderStatus: string
{
    case Pending   = 'pending';
    case Completed = 'completed';
    case Failed    = 'failed';
    case Refunded  = 'refunded';
}
