<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Modules\Billing\Models\PrepaidWallet;

class PrepaidWalletExhausted
{
    public function __construct(
        public readonly PrepaidWallet $wallet,
    ) {}
}
