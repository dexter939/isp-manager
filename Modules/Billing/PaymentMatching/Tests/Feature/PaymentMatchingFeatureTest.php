<?php
namespace Modules\Billing\PaymentMatching\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\PaymentMatching\Models\PaymentMatchingRule;
class PaymentMatchingFeatureTest extends TestCase {
    use RefreshDatabase;
    public function test_create_rule_via_api(): void {
        $this->actingAsAdmin()->postJson('/api/billing/matching-rules', ['name'=>'Test Rule','priority'=>5,'criteria'=>[['field'=>'variable_symbol','operator'=>'equals','match_against'=>'invoice_number','value'=>null]],'action'=>'match_oldest'])->assertStatus(201)->assertJsonPath('name', 'Test Rule');
    }
    public function test_cannot_delete_system_rule(): void {
        $rule = PaymentMatchingRule::factory()->create(['is_system'=>true]);
        $this->actingAsAdmin()->deleteJson("/api/billing/matching-rules/{$rule->id}")->assertStatus(422);
    }
    public function test_simulate_endpoint_works(): void {
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>true,'criteria'=>[['field'=>'variable_symbol','operator'=>'equals','match_against'=>'literal','value'=>'TEST123']],'action'=>'match_oldest']);
        $this->actingAsAdmin()->postJson('/api/billing/matching-rules/simulate', ['payment_data'=>['id'=>'p1','variable_symbol'=>'TEST123']])->assertOk()->assertJsonPath('wouldMatch', true);
    }
    public function test_reorder_rules(): void {
        $r1 = PaymentMatchingRule::factory()->create(['priority'=>1]);
        $r2 = PaymentMatchingRule::factory()->create(['priority'=>2]);
        $this->actingAsAdmin()->postJson('/api/billing/matching-rules/reorder', ['rules'=>[['id'=>$r1->id,'priority'=>5],['id'=>$r2->id,'priority'=>1]]])->assertOk();
        $this->assertEquals(5, $r1->fresh()->priority);
    }
}
