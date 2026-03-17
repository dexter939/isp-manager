<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit;

use Modules\AI\Models\WhatsAppMessage;
use Modules\AI\Services\WhatsAppService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.carrier_mock' => true]);
        $this->service = app(WhatsAppService::class);
    }

    /** @test */
    public function it_sends_text_message_in_mock_mode(): void
    {
        $message = $this->service->sendText(
            tenantId:  1,
            toNumber:  '+393331234567',
            body:      'Il tuo ticket TK-2024-000001 è stato aperto.',
        );

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertEquals('sent', $message->status);
        $this->assertNotNull($message->sent_at);
        $this->assertEquals('outbound', $message->direction);
    }

    /** @test */
    public function it_sends_template_in_mock_mode(): void
    {
        $message = $this->service->sendTemplate(
            tenantId:     1,
            toNumber:     '+393331234567',
            templateName: 'ticket_created_it',
            components:   [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'TK-2024-000001']]]],
        );

        $this->assertEquals('template', $message->message_type);
        $this->assertEquals('ticket_created_it', $message->template_name);
        $this->assertEquals('sent', $message->status);
    }

    /** @test */
    public function it_persists_message_to_database(): void
    {
        $this->service->sendText(1, '+393331234567', 'Test');

        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => 1,
            'to_number' => '+393331234567',
            'direction' => 'outbound',
            'status'    => 'sent',
        ]);
    }

    /** @test */
    public function it_verifies_webhook_signature(): void
    {
        $secret  = 'test_secret';
        config(['services.whatsapp.app_secret' => $secret]);

        $payload   = '{"entry":[]}';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $this->assertTrue($this->service->verifyWebhookSignature($payload, $signature));
        $this->assertFalse($this->service->verifyWebhookSignature($payload, 'sha256=invalid'));
    }

    /** @test */
    public function it_skips_duplicate_inbound_messages(): void
    {
        WhatsAppMessage::create([
            'tenant_id'      => 1,
            'direction'      => 'inbound',
            'from_number'    => '+393331234567',
            'to_number'      => 'TEST_PHONE_ID',
            'waba_message_id' => 'wamid.DUPLICATE123',
            'message_type'   => 'text',
            'body'           => 'Primo messaggio',
            'status'         => 'delivered',
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'id'   => 'wamid.DUPLICATE123',
                            'from' => '+393331234567',
                            'type' => 'text',
                            'text' => ['body' => 'Secondo messaggio (duplicato)'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->service->processInbound($payload, tenantId: 1);

        // Deve esistere solo 1 record con quell'ID
        $this->assertEquals(1, WhatsAppMessage::where('waba_message_id', 'wamid.DUPLICATE123')->count());
    }
}
