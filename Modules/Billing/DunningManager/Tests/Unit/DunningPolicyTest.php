<?php

namespace Modules\Billing\DunningManager\Tests\Unit;

use Modules\Billing\DunningManager\Models\DunningPolicy;
use Tests\TestCase;

class DunningPolicyTest extends TestCase
{
    public function test_default_policy_has_correct_steps(): void
    {
        $defaultSteps = config('dunning.default_policy.steps');

        $this->assertIsArray($defaultSteps);
        $this->assertNotEmpty($defaultSteps);

        $firstStep = $defaultSteps[0];
        $this->assertArrayHasKey('day', $firstStep);
        $this->assertArrayHasKey('action', $firstStep);
    }

    public function test_steps_jsonb_casting(): void
    {
        $steps = [
            ['day' => 3, 'action' => 'email'],
            ['day' => 14, 'action' => 'suspend'],
        ];

        $policy = new DunningPolicy(['steps' => $steps]);

        $this->assertIsArray($policy->steps);
        $this->assertCount(2, $policy->steps);
        $this->assertEquals('email', $policy->steps[0]['action']);
    }
}
