<?php

declare(strict_types=1);

namespace Modules\Maintenance\Tests\Unit;

use Modules\Contracts\Enums\ContractStatus;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;
use Modules\Maintenance\Models\TroubleTicket;
use Modules\Maintenance\Services\TicketService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TicketService::class);
    }

    /** @test */
    public function it_creates_ticket_with_correct_defaults(): void
    {
        $ticket = $this->service->create(
            tenantId:    1,
            title:       'Internet non funziona',
            description: 'Il cliente segnala assenza di connettività dal mattino',
            priority:    TicketPriority::High,
            type:        'assurance',
        );

        $this->assertInstanceOf(TroubleTicket::class, $ticket);
        $this->assertEquals(TicketStatus::Open, $ticket->status);
        $this->assertEquals(TicketPriority::High, $ticket->priority);
        $this->assertStringStartsWith('TK-', $ticket->ticket_number);
        $this->assertNotNull($ticket->due_at);
    }

    /** @test */
    public function it_generates_sequential_ticket_numbers(): void
    {
        $t1 = $this->service->create(1, 'Ticket 1', 'desc');
        $t2 = $this->service->create(1, 'Ticket 2', 'desc');

        $n1 = (int) explode('-', $t1->ticket_number)[2];
        $n2 = (int) explode('-', $t2->ticket_number)[2];

        $this->assertEquals(1, $n2 - $n1);
    }

    /** @test */
    public function it_sets_sla_due_at_based_on_priority(): void
    {
        $critical = $this->service->create(1, 'Critical', 'desc', TicketPriority::Critical);
        $low      = $this->service->create(1, 'Low', 'desc', TicketPriority::Low);

        $this->assertTrue($critical->due_at->isBefore($low->due_at));
    }

    /** @test */
    public function it_assigns_ticket_and_transitions_to_in_progress(): void
    {
        $ticket = $this->service->create(1, 'Test', 'desc');
        $this->service->assign($ticket, userId: 42);

        $this->assertEquals(42, $ticket->fresh()->assigned_to);
        $this->assertEquals(TicketStatus::InProgress, $ticket->fresh()->status);
    }

    /** @test */
    public function it_records_first_response_on_note(): void
    {
        $ticket = $this->service->create(1, 'Test', 'desc');
        $this->assertNull($ticket->first_response_at);

        $this->service->addNote($ticket, 'Prima risposta', userId: 1, isInternal: false);

        $this->assertNotNull($ticket->fresh()->first_response_at);
    }

    /** @test */
    public function it_does_not_overwrite_first_response_on_second_note(): void
    {
        $ticket = $this->service->create(1, 'Test', 'desc');
        $this->service->addNote($ticket, 'Prima nota', userId: 1, isInternal: false);

        $firstResponse = $ticket->fresh()->first_response_at;
        sleep(1); // ensure different timestamps

        $this->service->addNote($ticket, 'Seconda nota', userId: 1, isInternal: false);

        $this->assertEquals($firstResponse, $ticket->fresh()->first_response_at);
    }

    /** @test */
    public function it_resolves_ticket_with_notes(): void
    {
        $ticket = $this->service->create(1, 'Test', 'desc');
        $this->service->resolve($ticket, 'Problema risolto ripristinando ONT');

        $ticket->refresh();
        $this->assertEquals(TicketStatus::Resolved, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertEquals('Problema risolto ripristinando ONT', $ticket->resolution_notes);
    }

    /** @test */
    public function it_throws_on_invalid_status_transition(): void
    {
        $ticket = $this->service->create(1, 'Test', 'desc');
        // Open → Closed è non consentito (deve prima passare per Resolved)
        $this->service->transition($ticket, TicketStatus::Resolved);

        $this->expectException(\DomainException::class);
        $this->service->transition($ticket->fresh(), TicketStatus::InProgress); // Resolved → InProgress non consentito
    }

    /** @test */
    public function it_persists_ticket_to_database(): void
    {
        $ticket = $this->service->create(1, 'DB Test', 'desc', customerId: 5);

        $this->assertDatabaseHas('trouble_tickets', [
            'id'          => $ticket->id,
            'customer_id' => 5,
            'status'      => 'open',
        ]);
    }
}
