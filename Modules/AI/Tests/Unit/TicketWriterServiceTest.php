<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit;

use Modules\AI\Models\AiConversation;
use Modules\AI\Services\TicketWriterService;
use Modules\Maintenance\Enums\TicketPriority;
use Modules\Maintenance\Enums\TicketStatus;
use Modules\Maintenance\Models\TroubleTicket;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketWriterServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketWriterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.carrier_mock' => true]);
        $this->service = app(TicketWriterService::class);
    }

    /** @test */
    public function it_drafts_ticket_from_text_in_mock_mode(): void
    {
        ['ticket' => $ticket, 'conversation' => $conversation] = $this->service->draftFromText(
            tenantId:     1,
            customerText: 'Non riesco a navigare da stamattina, la luce ONT è rossa',
        );

        $this->assertInstanceOf(TroubleTicket::class, $ticket);
        $this->assertInstanceOf(AiConversation::class, $conversation);
        $this->assertEquals('ai', $ticket->source);
        $this->assertEquals(TicketStatus::Open, $ticket->status);
    }

    /** @test */
    public function it_creates_ai_conversation_record(): void
    {
        ['conversation' => $conversation] = $this->service->draftFromText(
            tenantId:     1,
            customerText: 'Problema connettività',
        );

        $this->assertDatabaseHas('ai_conversations', [
            'id'      => $conversation->id,
            'purpose' => 'ticket_draft',
            'status'  => 'completed',
        ]);
    }

    /** @test */
    public function it_links_conversation_to_generated_ticket(): void
    {
        ['ticket' => $ticket, 'conversation' => $conversation] = $this->service->draftFromText(
            tenantId:     1,
            customerText: 'Internet down',
        );

        $this->assertEquals($ticket->id, $conversation->fresh()->ticket_id);
    }

    /** @test */
    public function it_passes_customer_and_contract_ids(): void
    {
        ['ticket' => $ticket] = $this->service->draftFromText(
            tenantId:     1,
            customerText: 'Linea lenta',
            customerId:   42,
            contractId:   99,
        );

        $this->assertEquals(42, $ticket->customer_id);
        $this->assertEquals(99, $ticket->contract_id);
    }

    /** @test */
    public function it_returns_mock_reply_for_chat(): void
    {
        $conversation = AiConversation::create([
            'tenant_id' => 1,
            'channel'   => 'internal',
            'purpose'   => 'support',
            'status'    => 'active',
        ]);

        $reply = $this->service->chat($conversation, 'Quando arriverà il tecnico?');

        $this->assertIsString($reply);
        $this->assertNotEmpty($reply);
    }
}
