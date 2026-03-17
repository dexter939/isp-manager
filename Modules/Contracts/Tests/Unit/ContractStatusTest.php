<?php

declare(strict_types=1);

namespace Modules\Contracts\Tests\Unit;

use Modules\Contracts\Enums\ContractStatus;
use PHPUnit\Framework\TestCase;

class ContractStatusTest extends TestCase
{
    /** @test */
    public function draft_can_transition_to_pending_signature(): void
    {
        $this->assertTrue(ContractStatus::Draft->canTransitionTo(ContractStatus::PendingSignature));
    }

    /** @test */
    public function draft_cannot_jump_to_active(): void
    {
        $this->assertFalse(ContractStatus::Draft->canTransitionTo(ContractStatus::Active));
    }

    /** @test */
    public function pending_signature_can_go_back_to_draft(): void
    {
        $this->assertTrue(ContractStatus::PendingSignature->canTransitionTo(ContractStatus::Draft));
    }

    /** @test */
    public function active_contract_is_billable(): void
    {
        $this->assertTrue(ContractStatus::Active->isBillable());
    }

    /** @test */
    public function non_active_contracts_are_not_billable(): void
    {
        foreach ([ContractStatus::Draft, ContractStatus::PendingSignature, ContractStatus::Suspended, ContractStatus::Terminated] as $status) {
            $this->assertFalse($status->isBillable(), "{$status->value} should not be billable");
        }
    }

    /** @test */
    public function terminated_has_no_allowed_transitions(): void
    {
        $this->assertEmpty(ContractStatus::Terminated->allowedTransitions());
    }

    /** @test */
    public function only_pending_signature_can_be_signed(): void
    {
        $this->assertTrue(ContractStatus::PendingSignature->canBeSigned());

        foreach ([ContractStatus::Draft, ContractStatus::Active, ContractStatus::Suspended, ContractStatus::Terminated] as $s) {
            $this->assertFalse($s->canBeSigned(), "{$s->value} should not be signable");
        }
    }
}
