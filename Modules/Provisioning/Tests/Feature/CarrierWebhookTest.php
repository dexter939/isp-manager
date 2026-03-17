<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Provisioning\Enums\OrderState;
use Modules\Provisioning\Jobs\ProcessCarrierWebhookJob;
use Modules\Provisioning\Models\CarrierOrder;
use Tests\TestCase;

class CarrierWebhookTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /** @test */
    public function it_accepts_valid_of_webhook_and_returns_ack(): void
    {
        $payload = $this->buildOfStatusUpdateXml('ISP-2025-001', '0'); // Acquisito

        $this->postJson('/api/v1/webhooks/openfiber', [], ['Content-Type' => 'application/xml'])
            ->assertOk()
            ->assertSee('ACK');
    }

    /** @test */
    public function it_dispatches_async_job_on_of_webhook(): void
    {
        $payload = $this->buildOfStatusUpdateXml('ISP-2025-001', '2'); // Pianificato

        $this->call('POST', '/api/v1/webhooks/openfiber', [], [], [], [
            'CONTENT_TYPE'   => 'application/xml',
            'HTTP_X_REAL_IP' => '127.0.0.1', // mock whitelist bypass in test env
        ], $payload);

        Queue::assertPushed(ProcessCarrierWebhookJob::class, function ($job) {
            return $job->carrier === 'openfiber';
        });
    }

    /** @test */
    public function it_returns_400_for_empty_body(): void
    {
        $this->call('POST', '/api/v1/webhooks/openfiber', [], [], [], [
            'CONTENT_TYPE' => 'application/xml',
        ], '');

        $this->assertResponseStatus(400);
    }

    /** @test */
    public function process_webhook_job_updates_order_state(): void
    {
        Queue::restore(); // usa coda reale per testare il job

        $order = CarrierOrder::factory()->create([
            'codice_ordine_olo' => 'ISP-2025-TEST',
            'state'             => OrderState::Sent->value,
        ]);

        $payload = $this->buildOfStatusUpdateXml('ISP-2025-TEST', '0'); // → Accepted

        $job = new ProcessCarrierWebhookJob(
            carrier: 'openfiber',
            payload: $payload,
            sourceIp: '127.0.0.1',
        );

        $job->handle(
            gateway: app(\Modules\Provisioning\Services\CarrierGateway::class),
            stateMachine: app(\Modules\Provisioning\Services\OrderStateMachine::class),
        );

        $this->assertEquals(OrderState::Accepted, $order->fresh()->state);
    }

    /** @test */
    public function completion_order_sets_completed_state(): void
    {
        Queue::restore();

        $order = CarrierOrder::factory()->create([
            'codice_ordine_olo' => 'ISP-2025-COMP',
            'state'             => OrderState::InProgress->value,
        ]);

        $payload = $this->buildOfCompletionXml('ISP-2025-COMP', '0', 'ONT-SERIAL-12345');

        $job = new ProcessCarrierWebhookJob('openfiber', $payload, '127.0.0.1');
        $job->handle(
            gateway: app(\Modules\Provisioning\Services\CarrierGateway::class),
            stateMachine: app(\Modules\Provisioning\Services\OrderStateMachine::class),
        );

        $this->assertEquals(OrderState::Completed, $order->fresh()->state);
        $this->assertEquals('ONT-SERIAL-12345', $order->fresh()->id_apparato_consegnato);
    }

    /** @test */
    public function of_webhook_with_unknown_order_does_not_throw(): void
    {
        Queue::restore();

        $payload = $this->buildOfStatusUpdateXml('ISP-9999-UNKNOWN', '0');

        $job = new ProcessCarrierWebhookJob('openfiber', $payload, '127.0.0.1');

        // Non deve lanciare eccezioni — solo loggare warning
        $job->handle(
            gateway: app(\Modules\Provisioning\Services\CarrierGateway::class),
            stateMachine: app(\Modules\Provisioning\Services\OrderStateMachine::class),
        );

        $this->assertTrue(true); // se siamo qui, nessuna eccezione
    }

    // ---- Fixtures XML ----

    private function buildOfStatusUpdateXml(string $codiceOlo, string $stato): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <OF_StatusUpdate>
            <CODICE_ORDINE_OLO>{$codiceOlo}</CODICE_ORDINE_OLO>
            <CODICE_ORDINE_OF>OF-{$codiceOlo}</CODICE_ORDINE_OF>
            <STATO_ORDINE>{$stato}</STATO_ORDINE>
            <VLAN>200</VLAN>
            <GPON_DI_ATTESTAZIONE>GPON-A01-P01</GPON_DI_ATTESTAZIONE>
        </OF_StatusUpdate>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function buildOfCompletionXml(string $codiceOlo, string $stato, string $idApparato): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <OF_CompletionOrder_OpenStream>
            <CODICE_ORDINE_OLO>{$codiceOlo}</CODICE_ORDINE_OLO>
            <CODICE_ORDINE_OF>OF-{$codiceOlo}</CODICE_ORDINE_OF>
            <STATO_ORDINE>{$stato}</STATO_ORDINE>
            <ID_APPARATO_CONSEGNATO>{$idApparato}</ID_APPARATO_CONSEGNATO>
            <GPON_DI_ATTESTAZIONE>GPON-B02-P04</GPON_DI_ATTESTAZIONE>
        </OF_CompletionOrder_OpenStream>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }
}
