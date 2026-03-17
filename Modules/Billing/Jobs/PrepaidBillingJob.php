<?php

declare(strict_types=1);

namespace Modules\Billing\Jobs;

use Brick\Money\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Enums\PrepaidWalletStatus;
use Modules\Billing\Events\PrepaidWalletExhausted;
use Modules\Billing\Models\PrepaidWallet;
use Modules\Billing\Services\PrepaidWalletService;

/**
 * Job schedulato ogni giorno alle 02:00.
 *
 * Per ogni wallet prepaid attivo, addebita la quota mensile del servizio.
 * Se il saldo è insufficiente lancia PrepaidWalletExhausted.
 */
class PrepaidBillingJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(PrepaidWalletService $walletService): void
    {
        Log::info('PrepaidBillingJob: avvio ciclo di fatturazione prepaid');

        $processed = 0;
        $failed    = 0;

        // Use cursor() for memory efficiency
        PrepaidWallet::where('status', PrepaidWalletStatus::Active->value)
            ->cursor()
            ->each(function (PrepaidWallet $wallet) use ($walletService, &$processed, &$failed): void {
                try {
                    // Simplified: charge a placeholder monthly fee.
                    // In production this would look up the contract's monthly_fee.
                    $monthlyFee = $this->resolveMonthlyFee($wallet);

                    if ($monthlyFee === null || $monthlyFee->isZero()) {
                        return;
                    }

                    $walletService->charge(
                        wallet: $wallet,
                        amount: $monthlyFee,
                        description: 'Quota mensile servizio prepaid',
                        referenceId: null,
                    );

                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('PrepaidBillingJob: addebito fallito', [
                        'wallet_id' => $wallet->id,
                        'error'     => $e->getMessage(),
                    ]);

                    // If the wallet is not already exhausted, fire the event
                    if ($wallet->status !== PrepaidWalletStatus::Exhausted) {
                        event(new PrepaidWalletExhausted($wallet));
                    }
                }
            });

        Log::info("PrepaidBillingJob: completato — elaborati={$processed}, falliti={$failed}");
    }

    /**
     * Resolve the monthly fee for a wallet.
     * Looks up the customer's active contract service plan fee.
     * Returns null if no fee is applicable.
     */
    private function resolveMonthlyFee(PrepaidWallet $wallet): ?Money
    {
        // Loose coupling: load contract from the Contracts module if available.
        // This is a simplified implementation; the full version would join contracts
        // and service_plans to find the monthly_fee for this customer.
        try {
            if (class_exists(\Modules\Contracts\Models\Contract::class)) {
                $contract = \Modules\Contracts\Models\Contract::where('customer_id', $wallet->customer_id)
                    ->where('status', 'active')
                    ->first();

                if ($contract !== null && isset($contract->monthly_fee)) {
                    return Money::ofMinor((int) $contract->monthly_fee, $wallet->balance_currency);
                }
            }
        } catch (\Throwable) {
            // Contract module not available or schema differs — skip silently
        }

        return null;
    }
}
