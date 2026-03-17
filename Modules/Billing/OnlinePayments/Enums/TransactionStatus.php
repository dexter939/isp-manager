<?php

namespace Modules\Billing\OnlinePayments\Enums;

enum TransactionStatus: string
{
    case Pending   = 'pending';
    case Succeeded = 'succeeded';
    case Failed    = 'failed';
    case Refunded  = 'refunded';
}
