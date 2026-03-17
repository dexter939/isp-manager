<?php
namespace Modules\Billing\CustomerBalance\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class CustomerBalanceFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_get_balance_api(): void {
        $customer = $this->createCustomerInDb(['balance_amount' => 15000]);
        $this->actingAsAdmin()->getJson("/api/customers/{$customer->id}/balance")->assertOk()->assertJsonPath('balance_cents', 15000);
    }
    public function test_set_opening_balance_api(): void {
        $customer = $this->createCustomerInDb(['balance_amount' => 0]);
        $this->actingAsAdmin()->postJson("/api/customers/{$customer->id}/balance/opening", ['amount_cents'=>-10000,'date'=>'2024-01-01','note'=>'Migrazione da vecchio gestionale'])->assertOk();
        $this->assertEquals(-10000, DB::table('customers')->find($customer->id)->balance_amount);
    }
    public function test_statement_returns_movements(): void {
        $customer = $this->createCustomerInDb(['balance_amount' => 5000]);
        $this->actingAsAdmin()->getJson("/api/customers/{$customer->id}/balance/statement?from=2024-01-01&to=2024-12-31")->assertOk()->assertJsonStructure(['customer_id','from','to','movements']);
    }
    private function createCustomerInDb(array $attrs = []): object {
        $id = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('customers')->insert(array_merge(['id'=>$id,'name'=>'Test','email'=>'test@test.it','balance_amount'=>0,'balance_currency'=>'EUR'], $attrs));
        return \Illuminate\Support\Facades\DB::table('customers')->find($id);
    }
}
