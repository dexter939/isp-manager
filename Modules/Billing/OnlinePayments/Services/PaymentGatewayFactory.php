<?php

namespace Modules\Billing\OnlinePayments\Services;

use Modules\Billing\OnlinePayments\Enums\PaymentGateway;
use Stripe\StripeClient;

class PaymentGatewayFactory
{
    /**
     * Returns the appropriate gateway instance.
     */
    public function make(string|PaymentGateway $gateway): StripeGateway|NexiGateway
    {
        $key = $gateway instanceof PaymentGateway ? $gateway->value : $gateway;

        return match($key) {
            'stripe' => new StripeGateway(new StripeClient(config('online_payments.stripe.secret_key', ''))),
            'nexi'   => new NexiGateway(),
            default  => throw new \InvalidArgumentException("Unknown payment gateway: {$key}"),
        };
    }
}
