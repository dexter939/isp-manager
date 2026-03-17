<?php

declare(strict_types=1);

namespace Modules\Billing\Tests\Feature;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Billing\Database\Factories\PrepaidWalletFactory;
use Modules\Billing\Enums\PrepaidTransactionType;
use Modules\Billing\Enums\PrepaidWalletStatus;
use Modules\Billing\Events\PrepaidWalletExhausted;
use Modules\Billing\Models\PrepaidReseller;
use Modules\Billing\Models\PrepaidTopupProduct;
use Modules\Billing\Models\PrepaidTransaction;
use Modules\Billing\Models\PrepaidWallet;
use Modules\Billing\Services\PrepaidWalletService;
use Tests\TestCase;

class PrepaidWalletTest extends TestCase
{
    use RefreshDatabase;

    private PrepaidWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PrepaidWalletService::class);
    }

    private function makeWallet(int $balanceCents = 0): PrepaidWallet
    {
        return PrepaidWallet::create([
            'tenant_id'                    => (string) \Illuminate\Support\Str::uuid(),
            'customer_id'                  => $this->makeCustomerId(),
            'balance_amount'               => $balanceCents,
            'balance_currency'             => 'EUR',
            'status'                       => PrepaidWalletStatus::Active,
            'low_balance_threshold_amount' => 500,
            'auto_suspend_on_zero'         => true,
        ]);
    }

    private function makeCustomerId(): string
    {
        // Create a minimal customer record or return a fake UUID
        if (class_exists(\Modules\Contracts\Models\Customer::class)) {
            try {
                return \Modules\Contracts\Models\Customer::factory()->create()->id;
            } catch (\Throwable) {}
        }
        return (string) \Illuminate\Support\Str::uuid();
    }

    private function makeProduct(int $amountCents = 1000, int $bonusCents = 0): PrepaidTopupProduct
    {
        return PrepaidTopupProduct::create([
            'tenant_id'       => (string) \Illuminate\Support\Str::uuid(),
            'name'            => 'Test Product',
            'amount_amount'   => $amountCents,
            'amount_currency' => 'EUR',
            'bonus_amount'    => $bonusCents,
            'is_active'       => true,
            'sort_order'      => 0,
        ]);
    }

    /** @test */
    public function test_topup_increases_balance(): void
    {
        $wallet  = $this->makeWallet(0);
        $product = $this->makeProduct(1000, 200); // €10 + €2 bonus

        $transaction = $this->service->topup(
            wallet:        $wallet,
            product:       $product,
            paymentMethod: 'admin',
        );

        $wallet->refresh();

        $this->assertSame(1200, $wallet->balance_amount);
        $this->assertSame(PrepaidTransactionType::Topup, $transaction->type);
        $this->assertSame(1200, $transaction->amount_amount);
    }

    /** @test */
    public function test_charge_decreases_balance(): void
    {
        $wallet = $this->makeWallet(5000); // €50

        $transaction = $this->service->charge(
            wallet:      $wallet,
            amount:      Money::ofMinor(1500, 'EUR'),
            description: 'Quota mensile',
        );

        $wallet->refresh();

        $this->assertSame(3500, $wallet->balance_amount);
        $this->assertSame(PrepaidTransactionType::Charge, $transaction->type);
        $this->assertSame(1500, $transaction->amount_amount);
        $this->assertSame(5000, $transaction->balance_before_amount);
        $this->assertSame(3500, $transaction->balance_after_amount);
    }

    /** @test */
    public function test_charge_to_zero_suspends_wallet(): void
    {
        Event::fake([PrepaidWalletExhausted::class]);

        $wallet = $this->makeWallet(1000);

        $this->service->charge(
            wallet:      $wallet,
            amount:      Money::ofMinor(1000, 'EUR'),
            description: 'Svuotamento saldo',
        );

        $wallet->refresh();

        $this->assertSame(0, $wallet->balance_amount);
        $this->assertSame(PrepaidWalletStatus::Exhausted, $wallet->status);

        Event::assertDispatched(PrepaidWalletExhausted::class, function (PrepaidWalletExhausted $event) use ($wallet): bool {
            return $event->wallet->id === $wallet->id;
        });
    }

    /** @test */
    public function test_refund_restores_balance(): void
    {
        $wallet  = $this->makeWallet(5000);
        $product = $this->makeProduct(1000);

        $topupTransaction = $this->service->topup(
            wallet:        $wallet,
            product:       $product,
            paymentMethod: 'admin',
        );

        $wallet->refresh();
        $balanceAfterTopup = $wallet->balance_amount; // 6000

        $chargeTransaction = $this->service->charge(
            wallet:      $wallet,
            amount:      Money::ofMinor(500, 'EUR'),
            description: 'Addebito test',
        );

        $wallet->refresh();
        $this->assertSame($balanceAfterTopup - 500, $wallet->balance_amount);

        $refundTransaction = $this->service->refund($chargeTransaction);

        $wallet->refresh();
        $this->assertSame($balanceAfterTopup, $wallet->balance_amount);
        $this->assertSame(PrepaidTransactionType::Refund, $refundTransaction->type);
    }

    /** @test */
    public function test_reseller_commission_deducted(): void
    {
        // Reseller wallet starts with €100
        $resellerWallet = $this->makeWallet(10000);

        $reseller = PrepaidReseller::create([
            'tenant_id'               => $resellerWallet->tenant_id,
            'customer_id'             => $this->makeCustomerId(),
            'wallet_id'               => $resellerWallet->id,
            'commission_type'         => 'percentage',
            'commission_value_amount' => 1000, // 10%
            'is_active'               => true,
        ]);

        $customerWallet = $this->makeWallet(0);
        $product        = $this->makeProduct(2000); // €20

        $this->service->topup(
            wallet:        $customerWallet,
            product:       $product,
            paymentMethod: 'reseller',
            reseller:      $reseller,
        );

        // Commission = 10% of €20 = €2 (200 cents) credited to reseller wallet
        $resellerWallet->refresh();
        $this->assertSame(10200, $resellerWallet->balance_amount);

        // Commission transaction should exist
        $commissionTx = PrepaidTransaction::where('wallet_id', $resellerWallet->id)
            ->where('type', PrepaidTransactionType::Commission->value)
            ->first();

        $this->assertNotNull($commissionTx);
        $this->assertSame(200, $commissionTx->amount_amount);
    }
}
