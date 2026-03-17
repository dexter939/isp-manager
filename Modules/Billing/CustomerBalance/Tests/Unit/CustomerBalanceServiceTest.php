<?php
namespace Modules\Billing\CustomerBalance\Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\CustomerBalance\Services\CustomerBalanceService;
class CustomerBalanceServiceTest extends TestCase {
    use RefreshDatabase;
    private CustomerBalanceService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new CustomerBalanceService(); }
    public function test_apply_payment_increases_balance(): void {
        $customer = $this->createCustomer(['balance_amount' => 0]);
        $payment  = (object)['id'=>'pay-1','amount_cents'=>10000,'reference'=>'REF-001'];
        $this->service->applyPayment($customer, $payment);
        $this->assertEquals(10000, DB::table('customers')->find($customer->id)->balance_amount);
    }
    public function test_apply_invoice_decreases_balance(): void {
        $customer = $this->createCustomer(['balance_amount' => 5000]);
        $invoice  = (object)['id'=>'inv-1','amount_cents'=>3000,'number'=>'INV-001'];
        $this->service->applyInvoice($customer, $invoice);
        $this->assertEquals(2000, DB::table('customers')->find($customer->id)->balance_amount);
    }
    public function test_set_opening_balance_records_movement(): void {
        $customer = $this->createCustomer(['balance_amount' => 0]);
        $this->service->setOpeningBalance($customer, \Brick\Money\Money::ofMinor(-5000, 'EUR'), \Carbon\Carbon::parse('2024-01-01'), 'Legacy migration');
        $movement = DB::table('customer_balance_movements')->where('customer_id', $customer->id)->where('type','opening_balance')->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-5000, $movement->amount_amount);
    }
    private function createCustomer(array $attrs = []): object {
        $id = \Illuminate\Support\Str::uuid();
        DB::table('customers')->insert(array_merge(['id'=>$id,'name'=>'Test','email'=>'test@test.it','balance_amount'=>0,'balance_currency'=>'EUR'], $attrs));
        return DB::table('customers')->find($id);
    }
}
