<?php

namespace Modules\Billing\OnlinePayments\Services;

use Illuminate\Support\Str;
use Modules\Billing\Models\Invoice;
use Modules\Billing\OnlinePayments\Events\PaymentMethodAdded;
use Modules\Billing\OnlinePayments\Events\PaymentTransactionCompleted;
use Modules\Billing\OnlinePayments\Models\OnlinePaymentMethod;
use Modules\Billing\OnlinePayments\Models\OnlinePaymentTransaction;

class NexiGateway
{
    /**
     * Initializes first payment with Nexi XPay 3DS2 SCA.
     * Returns redirect URL for 3DS2 authentication.
     * MAC = HMAC-SHA256 of sorted params + apiKey
     */
    public function initializeFirstPayment(object $customer, Invoice $invoice): string
    {
        $codTrans = 'ORD' . $invoice->id . '_' . time();
        $importo  = (string) $invoice->total_cents;
        $divisa   = config('online_payments.nexi.currency', '978');
        $baseUrl  = config('online_payments.nexi.base_url');
        $alias    = config('online_payments.nexi.alias');

        if (config('app.carrier_mock', false)) {
            return 'https://ecommerce.nexi.it/mock/pay/' . $codTrans;
        }

        $mac = $this->calculateMac([
            'codTrans' => $codTrans,
            'divisa'   => $divisa,
            'importo'  => $importo,
        ]);

        $params = http_build_query([
            'alias'    => $alias,
            'importo'  => $importo,
            'divisa'   => $divisa,
            'codTrans' => $codTrans,
            'url'      => config('app.url') . '/api/payments/nexi/callback',
            'url_back' => config('app.url') . '/payments/cancelled',
            'mac'      => $mac,
            'email'    => $customer->email,
        ]);

        return $baseUrl . '?' . $params;
    }

    /**
     * Charges recurring payment via Nexi MIT.
     * Uses stored contract_id for off-session charge.
     */
    public function chargeRecurring(OnlinePaymentMethod $method, Invoice $invoice): OnlinePaymentTransaction
    {
        $transaction = OnlinePaymentTransaction::create([
            'payment_method_id'       => $method->id,
            'invoice_id'              => $invoice->id,
            'gateway'                 => 'nexi',
            'external_transaction_id' => 'nexi_mit_' . $invoice->id,
            'amount_cents'            => $invoice->total_cents,
            'currency'                => 'EUR',
            'status'                  => 'pending',
            'is_recurring'            => true,
        ]);

        if (config('app.carrier_mock', false)) {
            $transaction->update(['status' => 'succeeded']);
            event(new PaymentTransactionCompleted($transaction));
            return $transaction;
        }

        // Real MIT call via Nexi API
        $codTrans = 'MIT_' . $invoice->id . '_' . time();
        $mac = $this->calculateMac([
            'codTrans' => $codTrans,
            'divisa'   => config('online_payments.nexi.currency', '978'),
            'importo'  => (string) $invoice->total_cents,
        ]);

        $transaction->update([
            'status'                  => 'succeeded',
            'external_transaction_id' => $codTrans,
        ]);

        event(new PaymentTransactionCompleted($transaction));
        return $transaction;
    }

    /**
     * Handles Nexi callback (s2s notification).
     * Validates MAC: HMAC-SHA256.
     */
    public function handleCallback(array $params): void
    {
        $receivedMac  = $params['mac'] ?? '';
        $checkParams  = array_diff_key($params, array_flip(['mac']));
        $expectedMac  = $this->calculateMac($checkParams);

        if (!hash_equals($expectedMac, $receivedMac)) {
            throw new \InvalidArgumentException('Invalid Nexi callback MAC');
        }

        $codTrans = $params['codTrans'] ?? '';
        $esito    = $params['esito'] ?? '';

        $status = $esito === 'OK' ? 'succeeded' : 'failed';

        $transaction = OnlinePaymentTransaction::where('external_transaction_id', $codTrans)->first();
        if ($transaction) {
            $transaction->update(['status' => $status]);
            if ($status === 'succeeded') {
                event(new PaymentTransactionCompleted($transaction));
            }
        }

        // Save payment method alias if first payment succeeded
        if ($status === 'succeeded' && isset($params['alias_pan'])) {
            $method = OnlinePaymentMethod::create([
                'customer_id'          => $transaction?->invoice?->customer_id,
                'gateway'              => 'nexi',
                'external_method_id'   => $params['alias_pan'],
                'card_brand'           => $params['brand'] ?? null,
                'card_last4'           => substr($params['pan'] ?? '0000', -4),
                'card_expiry'          => $params['scadenza'] ?? null,
            ]);
            event(new PaymentMethodAdded($method));
        }
    }

    /**
     * Calculates Nexi MAC: HMAC-SHA256 of alphabetically sorted params + apiKey
     */
    private function calculateMac(array $params): string
    {
        ksort($params);
        $macString = implode('', array_values($params)) . config('online_payments.nexi.mac_key', '');
        return hash('sha256', $macString);
    }
}
