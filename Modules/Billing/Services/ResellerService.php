<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Enums\PrepaidTransactionDirection;
use Modules\Billing\Enums\PrepaidTransactionType;
use Modules\Billing\Enums\PrepaidWalletStatus;
use Modules\Billing\Models\PrepaidReseller;
use Modules\Billing\Models\PrepaidTransaction;
use Modules\Billing\Models\PrepaidWallet;

class ResellerService
{
    public function __construct(
        private readonly PrepaidWalletService $walletService,
    ) {}

    /**
     * Get current balance of the reseller's wallet.
     */
    public function getBalance(PrepaidReseller $reseller): Money
    {
        return $this->walletService->getBalance($reseller->wallet);
    }

    /**
     * Transfer credit from reseller wallet to a target customer wallet.
     */
    public function transferCredit(PrepaidReseller $reseller, PrepaidWallet $target, Money $amount): PrepaidTransaction
    {
        return DB::transaction(function () use ($reseller, $target, $amount): PrepaidTransaction {
            // Debit from reseller wallet
            $debitTransaction = $this->walletService->charge(
                $reseller->wallet,
                $amount,
                'Trasferimento credito al wallet ' . $target->id,
                $target->id,
            );

            // Calculate commission
            $commission = $reseller->calculateCommission($amount);

            // Credit to target wallet
            $resellerWallet = PrepaidWallet::lockForUpdate()->findOrFail($reseller->wallet_id);
            $targetWallet   = PrepaidWallet::lockForUpdate()->findOrFail($target->id);

            $balanceBefore          = $targetWallet->balance_amount;
            $targetWallet->balance_amount += (int) $amount->getMinorAmount()->toInt();

            if ($targetWallet->status === PrepaidWalletStatus::Exhausted) {
                $targetWallet->status = PrepaidWalletStatus::Active;
            }

            $targetWallet->save();

            $creditTransaction = PrepaidTransaction::create([
                'tenant_id'             => $targetWallet->tenant_id,
                'wallet_id'             => $targetWallet->id,
                'type'                  => PrepaidTransactionType::Topup,
                'amount_amount'         => (int) $amount->getMinorAmount()->toInt(),
                'amount_currency'       => $amount->getCurrency()->getCurrencyCode(),
                'direction'             => PrepaidTransactionDirection::Credit,
                'balance_before_amount' => $balanceBefore,
                'balance_after_amount'  => $targetWallet->balance_amount,
                'description'           => 'Ricarica da rivenditore ' . $reseller->id,
                'reference_id'          => $debitTransaction->id,
                'payment_method'        => 'reseller',
            ]);

            return $creditTransaction;
        });
    }

    /**
     * Get statement for a reseller in a date range, with transaction totals.
     *
     * @return array{transactions: \Illuminate\Database\Eloquent\Collection, total_credits: Money, total_debits: Money}
     */
    public function getStatement(PrepaidReseller $reseller, Carbon $from, Carbon $to): array
    {
        $transactions = PrepaidTransaction::where('wallet_id', $reseller->wallet_id)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderBy('created_at', 'desc')
            ->get();

        $currency    = $reseller->wallet->balance_currency ?? 'EUR';
        $totalCredits = Money::ofMinor(0, $currency);
        $totalDebits  = Money::ofMinor(0, $currency);

        foreach ($transactions as $tx) {
            if ($tx->direction === PrepaidTransactionDirection::Credit) {
                $totalCredits = $totalCredits->plus(Money::ofMinor($tx->amount_amount, $tx->amount_currency));
            } else {
                $totalDebits = $totalDebits->plus(Money::ofMinor($tx->amount_amount, $tx->amount_currency));
            }
        }

        return [
            'transactions'  => $transactions,
            'total_credits' => $totalCredits,
            'total_debits'  => $totalDebits,
        ];
    }
}
