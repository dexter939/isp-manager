<?php

declare(strict_types=1);

namespace Modules\Billing\Tests\Unit;

use Modules\Billing\Models\PrepaidTopupProduct;
use Modules\Billing\Services\PayPalPaymentService;
use Tests\TestCase;

class PayPalPaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Enable carrier mock mode for these tests
        config(['carrier_mock' => true]);
    }

    /** @test */
    public function test_create_order_returns_mock_id_in_mock_mode(): void
    {
        $product = new PrepaidTopupProduct();
        $product->forceFill([
            'id'              => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'       => (string) \Illuminate\Support\Str::uuid(),
            'name'            => 'Test Ricarica €10',
            'amount_amount'   => 1000,
            'amount_currency' => 'EUR',
            'bonus_amount'    => 0,
            'is_active'       => true,
            'sort_order'      => 0,
        ]);

        $service = app(PayPalPaymentService::class);
        $orderId = $service->createOrder($product);

        $this->assertStringStartsWith('MOCK-ORDER-', $orderId);
        $this->assertSame(21, strlen($orderId)); // 'MOCK-ORDER-' (11) + random(10)
    }

    /** @test */
    public function test_capture_order_returns_true_in_mock_mode(): void
    {
        $service = app(PayPalPaymentService::class);
        $result  = $service->captureOrder('MOCK-ORDER-ABCDEFGHIJ');

        $this->assertTrue($result);
    }
}
