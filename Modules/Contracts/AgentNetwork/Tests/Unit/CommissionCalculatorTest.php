<?php

namespace Modules\Contracts\AgentNetwork\Tests\Unit;

use Brick\Money\Money;
use Modules\Contracts\AgentNetwork\Models\Agent;
use Modules\Contracts\AgentNetwork\Models\CommissionRule;
use Modules\Contracts\AgentNetwork\Services\CommissionCalculator;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CommissionCalculator();
    }

    public function test_calculates_percentage_commission(): void
    {
        $agent    = new Agent(['commission_rate' => 10.0]);
        $contract = (object) ['offer_type' => null, 'id' => 1];
        $amount   = Money::of(100, 'EUR');

        $commission = $this->calculator->calculate($agent, $contract, $amount);

        $this->assertEquals(Money::of(10, 'EUR'), $commission);
    }

    public function test_calculates_fixed_commission(): void
    {
        $rule = new CommissionRule([
            'rate_type'       => 'fixed',
            'rate_value_cents'=> 2500,
        ]);

        $amount = $rule->rate_type === 'fixed'
            ? Money::ofMinor($rule->rate_value_cents, 'EUR')
            : Money::of(0, 'EUR');

        $this->assertEquals(Money::of(25, 'EUR'), $amount);
    }

    public function test_resolves_specific_rule_over_default(): void
    {
        $agent         = Agent::factory()->create(['commission_rate' => 5.0]);
        $agentRule     = CommissionRule::factory()->create([
            'agent_id'        => $agent->id,
            'rate_percentage' => 15.0,
            'priority'        => 10,
        ]);
        $globalRule    = CommissionRule::factory()->create([
            'agent_id'        => null,
            'rate_percentage' => 8.0,
            'priority'        => 0,
        ]);

        $contract = (object) ['offer_type' => null, 'id' => 1];
        $resolved = $this->calculator->resolveRule($agent, $contract);

        $this->assertEquals($agentRule->id, $resolved->id);
    }
}
