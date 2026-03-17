<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Brick\Money\Money;
use Brick\Money\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Enums\PrepaidTransactionDirection;
use Modules\Billing\Enums\PrepaidTransactionType;
use Modules\Billing\Enums\PrepaidWalletStatus;
use Modules\Billing\Enums\PrepaidOrderStatus;
use Modules\Billing\Events\PrepaidTopupCompleted;
use Modules\Billing\Events\PrepaidWalletExhausted;
use Modules\Billing\Models\PrepaidReseller;
use Modules\Billing\Models\PrepaidTopupOrder;
use Modules\Billing\Models\PrepaidTopupProduct;
use Modules\Billing\Models\PrepaidTransaction;
use Modules\Billing\Models\PrepaidWallet;

class PrepaidWalletService
{
    /**
     * Create a new prepaid wallet for a customer.
     */
    public function createWallet(string $customerId, string $tenantId, array $config = []): PrepaidWallet
    {
        $defaults = [
            'customer_id'                  => $customerId,
            'tenant_id'                    => $tenantId,
            'balance_amount'               => 0,
            'balance_currency'             => config('prepaid.currency', 'EUR'),
            'status'                       => PrepaidWalletStatus::Active,
            'low_balance_threshold_amount' => config('prepaid.low_balance_threshold_default_cents', 500),
            'auto_suspend_on_zero'         => config('prepaid.auto_suspend_on_zero', true),
        ];

        $attributes = array_merge($defaults, array_intersect_key($config, array_flip([
            'low_balance_threshold_amount',
            'auto_suspend_on_zero',
            'balance_currency',
        ])));

        return PrepaidWallet::create($attributes);
    }

    /**
     * Top up a wallet with a product purchase.
     */
    public function topup(
        PrepaidWallet $wallet,
        PrepaidTopupProduct $product,
        string $paymentMethod,
        ?string $paymentReference = null,
        ?PrepaidReseller $reseller = null,
    ): PrepaidTransaction {
        return DB::transaction(function () use ($wallet, $product, $paymentMethod, $paymentReference, $reseller): PrepaidTransaction {
            // 1. Lock wallet row
            $wallet = PrepaidWallet::lockForUpdate()->findOrFail($wallet->id);

            // 2. Calculate total = product amount + bonus
            $productAmount = Money::ofMinor($product->amount_amount, $product->amount_currency);
            $bonusAmount   = Money::ofMinor($product->bonus_amount, $product->amount_currency);
            $total         = $productAmount->plus($bonusAmount);

            // 3. Calculate reseller commission if $reseller provided
            $commissionMoney = null;
            if ($reseller !== null) {
                $commissionMoney = $reseller->calculateCommission($productAmount);
            }

            // 4. Balance before
            $balanceBefore = $wallet->balance_amount;

            // 5. Add total to wallet
            $wallet->balance_amount += (int) $total->getMinorAmount()->toInt();

            // 6. If wallet was exhausted → reactivate
            if ($wallet->status === PrepaidWalletStatus::Exhausted) {
                $wallet->status = PrepaidWalletStatus::Active;
            }

            // 7. Save wallet
            $wallet->save();

            $balanceAfter = $wallet->balance_amount;

            // 8. Create PrepaidTransaction (topup, credit)
            /** @var PrepaidTransaction $transaction */
            $transaction = PrepaidTransaction::create([
                'tenant_id'             => $wallet->tenant_id,
                'wallet_id'             => $wallet->id,
                'type'                  => PrepaidTransactionType::Topup,
                'amount_amount'         => (int) $total->getMinorAmount()->toInt(),
                'amount_currency'       => $product->amount_currency,
                'direction'             => PrepaidTransactionDirection::Credit,
                'balance_before_amount' => $balanceBefore,
                'balance_after_amount'  => $balanceAfter,
                'description'           => 'Top-up: ' . $product->name,
                'payment_method'        => $paymentMethod,
            ]);

            // 9. Create PrepaidTopupOrder (status=completed)
            $order = PrepaidTopupOrder::create([
                'tenant_id'         => $wallet->tenant_id,
                'wallet_id'         => $wallet->id,
                'product_id'        => $product->id,
                'reseller_id'       => $reseller?->id,
                'amount_amount'     => (int) $productAmount->getMinorAmount()->toInt(),
                'amount_currency'   => $product->amount_currency,
                'commission_amount' => $commissionMoney !== null
                    ? (int) $commissionMoney->getMinorAmount()->toInt()
                    : null,
                'payment_method'    => $paymentMethod,
                'payment_reference' => $paymentReference,
                'status'            => PrepaidOrderStatus::Completed,
                'completed_at'      => now(),
            ]);

            // 10. If reseller: create commission transaction (debit from reseller wallet)
            if ($reseller !== null && $commissionMoney !== null) {
                $resellerWallet      = PrepaidWallet::lockForUpdate()->findOrFail($reseller->wallet_id);
                $resellerBalanceBefore = $resellerWallet->balance_amount;
                $resellerWallet->balance_amount += (int) $commissionMoney->getMinorAmount()->toInt();
                $resellerWallet->save();

                PrepaidTransaction::create([
                    'tenant_id'             => $wallet->tenant_id,
                    'wallet_id'             => $reseller->wallet_id,
                    'type'                  => PrepaidTransactionType::Commission,
                    'amount_amount'         => (int) $commissionMoney->getMinorAmount()->toInt(),
                    'amount_currency'       => $product->amount_currency,
                    'direction'             => PrepaidTransactionDirection::Debit,
                    'balance_before_amount' => $resellerBalanceBefore,
                    'balance_after_amount'  => $resellerWallet->balance_amount,
                    'description'           => 'Commissione top-up: ' . $product->name,
                    'reference_id'          => $transaction->id,
                    'payment_method'        => $paymentMethod,
                ]);
            }

            // 11. Fire PrepaidTopupCompleted event
            event(new PrepaidTopupCompleted($order, $transaction));

            // 12. Return transaction
            return $transaction;
        });
    }

    /**
     * Charge an amount from a wallet.
     */
    public function charge(
        PrepaidWallet $wallet,
        Money $amount,
        string $description,
        ?string $referenceId = null,
    ): PrepaidTransaction {
        return DB::transaction(function () use ($wallet, $amount, $description, $referenceId): PrepaidTransaction {
            // Lock wallet
            $wallet = PrepaidWallet::lockForUpdate()->findOrFail($wallet->id);

            // 1. Balance before
            $balanceBefore = $wallet->balance_amount;

            // 2. Deduct amount
            $wallet->balance_amount -= (int) $amount->getMinorAmount()->toInt();

            // 3. Low balance warning
            if ($wallet->balance_amount < $wallet->low_balance_threshold_amount && $wallet->balance_amount > 0) {
                Log::warning('Prepaid wallet low balance', [
                    'wallet_id'       => $wallet->id,
                    'balance_amount'  => $wallet->balance_amount,
                    'threshold'       => $wallet->low_balance_threshold_amount,
                ]);
            }

            // 4. Handle zero / negative balance
            if ($wallet->balance_amount <= 0) {
                $wallet->balance_amount = 0;
                $wallet->status         = PrepaidWalletStatus::Exhausted;
                $wallet->save();

                event(new PrepaidWalletExhausted($wallet));

                if ($wallet->auto_suspend_on_zero) {
                    $this->suspendPppoeAccount($wallet);
                }
            } else {
                // 5. Save wallet
                $wallet->save();
            }

            $balanceAfter = $wallet->balance_amount;

            // 6. Create transaction
            $transaction = PrepaidTransaction::create([
                'tenant_id'             => $wallet->tenant_id,
                'wallet_id'             => $wallet->id,
                'type'                  => PrepaidTransactionType::Charge,
                'amount_amount'         => (int) $amount->getMinorAmount()->toInt(),
                'amount_currency'       => $amount->getCurrency()->getCurrencyCode(),
                'direction'             => PrepaidTransactionDirection::Debit,
                'balance_before_amount' => $balanceBefore,
                'balance_after_amount'  => $balanceAfter,
                'description'           => $description,
                'reference_id'          => $referenceId,
            ]);

            // 7. Return transaction
            return $transaction;
        });
    }

    /**
     * Refund a full or partial amount from an original transaction.
     */
    public function refund(PrepaidTransaction $original, ?Money $amount = null): PrepaidTransaction
    {
        return DB::transaction(function () use ($original, $amount): PrepaidTransaction {
            $wallet = PrepaidWallet::lockForUpdate()->findOrFail($original->wallet_id);

            $refundAmount = $amount ?? Money::ofMinor($original->amount_amount, $original->amount_currency);

            $balanceBefore          = $wallet->balance_amount;
            $wallet->balance_amount += (int) $refundAmount->getMinorAmount()->toInt();

            // Reactivate if was exhausted
            if ($wallet->status === PrepaidWalletStatus::Exhausted) {
                $wallet->status = PrepaidWalletStatus::Active;
            }

            $wallet->save();

            return PrepaidTransaction::create([
                'tenant_id'             => $wallet->tenant_id,
                'wallet_id'             => $wallet->id,
                'type'                  => PrepaidTransactionType::Refund,
                'amount_amount'         => (int) $refundAmount->getMinorAmount()->toInt(),
                'amount_currency'       => $refundAmount->getCurrency()->getCurrencyCode(),
                'direction'             => PrepaidTransactionDirection::Credit,
                'balance_before_amount' => $balanceBefore,
                'balance_after_amount'  => $wallet->balance_amount,
                'description'           => 'Rimborso transazione #' . $original->id,
                'reference_id'          => $original->id,
            ]);
        });
    }

    /**
     * Return current balance as a Money object.
     */
    public function getBalance(PrepaidWallet $wallet): Money
    {
        return Money::ofMinor($wallet->balance_amount, $wallet->balance_currency);
    }

    /**
     * Fires PrepaidWalletExhausted — the Network module's listener handles RADIUS suspend.
     * Loose coupling via event; no direct service injection.
     */
    private function suspendPppoeAccount(PrepaidWallet $wallet): void
    {
        Log::info('Prepaid: suspending PPPoE account via event', [
            'wallet_id'   => $wallet->id,
            'customer_id' => $wallet->customer_id,
        ]);

        // The PrepaidWalletExhausted event has already been fired in charge().
        // The Network module's listener will intercept it and issue a RADIUS CoA/Disconnect.
    }
}
