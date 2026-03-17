<?php
namespace Modules\Billing\PaymentMatching\Tests\Unit;
use Tests\TestCase;
use Modules\Billing\PaymentMatching\Services\PaymentMatchingEngine;
use Modules\Billing\PaymentMatching\Models\PaymentMatchingRule;
class PaymentMatchingEngineTest extends TestCase {
    private PaymentMatchingEngine $engine;
    protected function setUp(): void { parent::setUp(); $this->engine = new PaymentMatchingEngine(); }
    public function test_simulate_matches_variable_symbol_equals_invoice_number(): void {
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>true,'criteria'=>[['field'=>'variable_symbol','operator'=>'equals','match_against'=>'invoice_number','value'=>null]],'action'=>'match_oldest']);
        $result = $this->engine->simulate(['id'=>'test-pay','variable_symbol'=>'INV-2024-0001','invoice_id'=>'inv-uuid-1']);
        $this->assertTrue($result->wouldMatch);
        $this->assertEquals('match_oldest', $result->actionTaken);
    }
    public function test_simulate_no_match_returns_false(): void {
        $result = $this->engine->simulate(['id'=>'test-pay','variable_symbol'=>'XYZ','amount_cents'=>1000]);
        $this->assertFalse($result->wouldMatch);
        $this->assertEquals('no_match', $result->actionTaken);
    }
    public function test_simulate_contains_operator(): void {
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>true,'criteria'=>[['field'=>'payment_note','operator'=>'contains','match_against'=>'literal','value'=>'FATT']],'action'=>'add_to_credit']);
        $result = $this->engine->simulate(['id'=>'p1','note'=>'Pagamento FATT-2024-001']);
        $this->assertTrue($result->wouldMatch);
    }
    public function test_priority_order_first_rule_wins(): void {
        PaymentMatchingRule::factory()->create(['priority'=>2,'is_active'=>true,'criteria'=>[['field'=>'variable_symbol','operator'=>'equals','match_against'=>'literal','value'=>'MATCH']],'action'=>'add_to_credit']);
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>true,'criteria'=>[['field'=>'variable_symbol','operator'=>'equals','match_against'=>'literal','value'=>'MATCH']],'action'=>'match_oldest']);
        $result = $this->engine->simulate(['id'=>'p1','variable_symbol'=>'MATCH']);
        $this->assertTrue($result->wouldMatch);
        $this->assertEquals('match_oldest', $result->actionTaken); // priority 1 wins
    }
    public function test_inactive_rule_skipped(): void {
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>false,'criteria'=>[['field'=>'variable_symbol','operator'=>'equals','match_against'=>'literal','value'=>'MATCH']],'action'=>'match_oldest']);
        $result = $this->engine->simulate(['id'=>'p1','variable_symbol'=>'MATCH']);
        $this->assertFalse($result->wouldMatch);
    }
    public function test_greater_than_operator(): void {
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>true,'criteria'=>[['field'=>'amount','operator'=>'greater_than','match_against'=>'literal','value'=>'50000']],'action'=>'skip']);
        $result = $this->engine->simulate(['id'=>'p1','amount_cents'=>100000]);
        $this->assertTrue($result->wouldMatch);
        $this->assertEquals('skip', $result->actionTaken);
    }
    public function test_less_than_operator(): void {
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>true,'criteria'=>[['field'=>'amount','operator'=>'less_than','match_against'=>'literal','value'=>'1000']],'action'=>'skip']);
        $result = $this->engine->simulate(['id'=>'p1','amount_cents'=>500]);
        $this->assertTrue($result->wouldMatch);
    }
    public function test_multi_criteria_all_must_match(): void {
        PaymentMatchingRule::factory()->create(['priority'=>1,'is_active'=>true,'criteria'=>[['field'=>'variable_symbol','operator'=>'equals','match_against'=>'literal','value'=>'VAR123'],['field'=>'amount','operator'=>'greater_than','match_against'=>'literal','value'=>'0']],'action'=>'match_oldest']);
        // Both criteria match
        $resultMatch = $this->engine->simulate(['id'=>'p1','variable_symbol'=>'VAR123','amount_cents'=>500]);
        $this->assertTrue($resultMatch->wouldMatch);
        // Only first criterion matches
        $resultNoMatch = $this->engine->simulate(['id'=>'p2','variable_symbol'=>'VAR123','amount_cents'=>0]);
        $this->assertFalse($resultNoMatch->wouldMatch);
    }
}
