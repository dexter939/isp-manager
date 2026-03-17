<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Feature;

use Modules\AI\Models\AiConversation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AIApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.carrier_mock' => true]);
    }

    // ── Ticket Writer ─────────────────────────────────────────────────────────

    /** @test */
    public function it_drafts_ticket_from_text(): void
    {
        $user = $this->makeUser(tenantId: 1);

        $this->actingAs($user)
            ->postJson('/api/v1/ai/draft-ticket', [
                'text' => 'Non riesco a navigare da stamattina, la luce ONT è rossa',
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'ticket'       => ['id', 'ticket_number', 'title', 'status'],
                'conversation' => ['id', 'total_input_tokens', 'total_output_tokens'],
            ]);
    }

    /** @test */
    public function it_validates_draft_ticket_input(): void
    {
        $user = $this->makeUser(tenantId: 1);

        $this->actingAs($user)
            ->postJson('/api/v1/ai/draft-ticket', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    /** @test */
    public function it_requires_auth_for_draft_ticket(): void
    {
        $this->postJson('/api/v1/ai/draft-ticket', ['text' => 'test'])
            ->assertUnauthorized();
    }

    /** @test */
    public function it_responds_to_chat_messages(): void
    {
        $user = $this->makeUser(tenantId: 1);

        $conversation = AiConversation::create([
            'tenant_id' => 1,
            'channel'   => 'internal',
            'purpose'   => 'support',
            'status'    => 'active',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/ai/conversations/{$conversation->id}/chat", [
                'message' => 'Quando arriverà il tecnico?',
            ])
            ->assertOk()
            ->assertJsonStructure(['reply']);
    }

    /** @test */
    public function it_rejects_chat_on_completed_conversation(): void
    {
        $user = $this->makeUser(tenantId: 1);

        $conversation = AiConversation::create([
            'tenant_id' => 1,
            'channel'   => 'internal',
            'purpose'   => 'support',
            'status'    => 'completed',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/ai/conversations/{$conversation->id}/chat", [
                'message' => 'Test',
            ])
            ->assertUnprocessable();
    }

    // ── WhatsApp Webhook ──────────────────────────────────────────────────────

    /** @test */
    public function it_verifies_whatsapp_webhook(): void
    {
        config(['services.whatsapp.verify_token' => 'my_verify_token']);

        $this->getJson('/api/ai/whatsapp/webhook?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'my_verify_token',
            'hub_challenge'    => '12345',
        ]))
            ->assertOk()
            ->assertSee('12345');
    }

    /** @test */
    public function it_rejects_invalid_webhook_verify_token(): void
    {
        config(['services.whatsapp.verify_token' => 'correct_token']);

        $this->getJson('/api/ai/whatsapp/webhook?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge'    => '12345',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function it_rejects_whatsapp_webhook_with_invalid_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'secret']);

        $this->postJson('/api/ai/whatsapp/webhook', [], [
            'X-Hub-Signature-256' => 'sha256=invalidsignature',
        ])
            ->assertUnauthorized();
    }
}
