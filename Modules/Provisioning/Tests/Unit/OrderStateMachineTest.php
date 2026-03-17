<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Unit;

use Modules\Provisioning\Enums\OrderState;
use PHPUnit\Framework\TestCase;

class OrderStateMachineTest extends TestCase
{
    /** @test */
    public function draft_can_transition_to_sent(): void
    {
        $this->assertTrue(OrderState::Draft->canTransitionTo(OrderState::Sent));
    }

    /** @test */
    public function draft_cannot_jump_to_completed(): void
    {
        $this->assertFalse(OrderState::Draft->canTransitionTo(OrderState::Completed));
    }

    /** @test */
    public function sent_maps_to_accepted_on_of_status_0(): void
    {
        $this->assertEquals(OrderState::Accepted, OrderState::fromOfStatusCode('0'));
    }

    /** @test */
    public function of_status_1_maps_to_ko(): void
    {
        $this->assertEquals(OrderState::Ko, OrderState::fromOfStatusCode('1'));
    }

    /** @test */
    public function of_status_2_maps_to_scheduled(): void
    {
        $this->assertEquals(OrderState::Scheduled, OrderState::fromOfStatusCode('2'));
    }

    /** @test */
    public function of_status_3_maps_to_cancelled(): void
    {
        $this->assertEquals(OrderState::Cancelled, OrderState::fromOfStatusCode('3'));
    }

    /** @test */
    public function of_status_4_maps_to_suspended(): void
    {
        $this->assertEquals(OrderState::Suspended, OrderState::fromOfStatusCode('4'));
    }

    /** @test */
    public function of_status_5_maps_to_completed(): void
    {
        $this->assertEquals(OrderState::Completed, OrderState::fromOfStatusCode('5'));
    }

    /** @test */
    public function of_status_6_maps_to_ko(): void
    {
        $this->assertEquals(OrderState::Ko, OrderState::fromOfStatusCode('6'));
    }

    /** @test */
    public function of_status_7_maps_to_scheduled(): void
    {
        $this->assertEquals(OrderState::Scheduled, OrderState::fromOfStatusCode('7'));
    }

    /** @test */
    public function unknown_of_status_throws(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        OrderState::fromOfStatusCode('99');
    }

    /** @test */
    public function completed_and_cancelled_are_final(): void
    {
        $this->assertTrue(OrderState::Completed->isFinal());
        $this->assertTrue(OrderState::Cancelled->isFinal());
        $this->assertTrue(OrderState::RetryFailed->isFinal());
    }

    /** @test */
    public function ko_is_retryable(): void
    {
        $this->assertTrue(OrderState::Ko->isRetryable());
        $this->assertTrue(OrderState::RetryFailed->isRetryable());
        $this->assertFalse(OrderState::Completed->isRetryable());
    }

    /** @test */
    public function suspended_can_go_back_to_accepted(): void
    {
        $this->assertTrue(OrderState::Suspended->canTransitionTo(OrderState::Accepted));
    }

    /** @test */
    public function completed_has_no_transitions(): void
    {
        $this->assertEmpty(OrderState::Completed->allowedTransitions());
    }
}
