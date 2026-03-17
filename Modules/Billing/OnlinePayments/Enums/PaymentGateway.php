<?php

namespace Modules\Billing\OnlinePayments\Enums;

enum PaymentGateway: string
{
    case Stripe = 'stripe';
    case Nexi   = 'nexi';
}
