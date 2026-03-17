<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PrepaidTransactionDirection: string
{
    case Credit = 'credit';
    case Debit  = 'debit';
}
