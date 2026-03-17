<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PrepaidPaymentMethod: string
{
    case Paypal       = 'paypal';
    case BankTransfer = 'bank_transfer';
    case Reseller     = 'reseller';
    case Admin        = 'admin';
}
