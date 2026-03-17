<?php

namespace Modules\Billing\Sdi\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Sdi\Models\SdiTransmission;
use Modules\Billing\Sdi\Services\SdiTransmissionService;
use Tests\TestCase;

class SdiTransmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_transmit_invoice_mock_mode(): void
    {
        config(['app.carrier_mock' => true, 'sdi.channel' => 'aruba']);

        $invoice = Invoice::factory()->create();
        /** @var SdiTransmissionService $service */
        $service = app(SdiTransmissionService::class);

        $transmission = $service->send($invoice, 'aruba');

        $this->assertInstanceOf(SdiTransmission::class, $transmission);
        $this->assertEquals('delivered', $transmission->status);
        $this->assertDatabaseHas('sdi_transmissions', ['id' => $transmission->id]);
    }

    public function test_processes_rc_notification(): void
    {
        $transmission = SdiTransmission::factory()->create(['status' => 'sent']);
        /** @var SdiTransmissionService $service */
        $service = app(SdiTransmissionService::class);

        $service->processNotification('RC', (string) $transmission->id, '{"notification_type":"RC"}');

        $transmission->refresh();
        $this->assertEquals('delivered', $transmission->status);
    }

    public function test_processes_ns_notification(): void
    {
        $transmission = SdiTransmission::factory()->create(['status' => 'sent']);
        /** @var SdiTransmissionService $service */
        $service = app(SdiTransmissionService::class);

        $service->processNotification('NS', (string) $transmission->id, '{"notification_type":"NS"}');

        $transmission->refresh();
        $this->assertEquals('rejected', $transmission->status);
    }

    public function test_webhook_validates_hmac(): void
    {
        $response = $this->postJson('/api/sdi/webhook/aruba', ['test' => 1], [
            'X-Aruba-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(403);
    }
}
