<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Unit;

use Modules\Provisioning\Services\Drivers\OpenFiberDriver;
use Modules\Provisioning\Services\XmlParser\OpenFiberXmlParser;
use PHPUnit\Framework\TestCase;

/**
 * Testa il parsing dei payload XML Open Fiber usando le fixture reali.
 * Non chiama il carrier (usa il parser direttamente).
 */
class OpenFiberDriverTest extends TestCase
{
    private OpenFiberXmlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new OpenFiberXmlParser();
    }

    /** @test */
    public function it_parses_status_update_webhook(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/openfiber_status_update.xml'));

        $result = $this->parser->parseWebhook($xml);

        $this->assertEquals('OF_StatusUpdate', $result->type);
        $this->assertEquals('ISP-2025-001234', $result->codiceOrdineOlo);
        $this->assertEquals('OF-20250401-001234', $result->codiceOrdineOf);
        $this->assertEquals('0', $result->statoOrdine);  // Acquisito
    }

    /** @test */
    public function it_parses_completion_order_webhook(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/openfiber_completion_order.xml'));

        $result = $this->parser->parseWebhook($xml);

        $this->assertEquals('OF_CompletionOrder_OpenStream', $result->type);
        $this->assertEquals('ISP-2025-001234', $result->codiceOrdineOlo);
        $this->assertEquals('3', $result->statoOrdine);  // Completato
        $this->assertEquals('ONT-HUAWEI-A8B3C2D1E0F5', $result->idApparatoConsegnato);
        $this->assertEquals('GPON-B02-P04', $result->gponAttestazione);
    }

    /** @test */
    public function status_update_stato_0_maps_to_accepted(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/openfiber_status_update.xml'));

        $result = $this->parser->parseWebhook($xml);

        // STATO_ORDINE=0 deve mappare allo stato "accepted" nel nostro state machine
        $mappedState = $this->parser->mapStatoOrdineToOrderState($result->statoOrdine);

        $this->assertEquals('accepted', $mappedState->value);
    }

    /** @test */
    public function completion_order_stato_3_maps_to_completed(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/openfiber_completion_order.xml'));

        $result = $this->parser->parseWebhook($xml);

        $mappedState = $this->parser->mapStatoOrdineToOrderState($result->statoOrdine);

        $this->assertEquals('completed', $mappedState->value);
    }

    /** @test */
    public function it_parses_line_test_ok_response(): void
    {
        $json = file_get_contents(base_path('tests/Fixtures/line_test_response_ok.json'));
        $data = json_decode($json, true);

        $result = \Modules\Provisioning\Data\LineStatusResult::fromOfV23Response($data);

        $this->assertTrue($result->success);
        $this->assertEquals('OK', $result->result);
        $this->assertFalse($result->isRetryable);
        $this->assertFalse($result->requiresTicket);
    }

    /** @test */
    public function it_parses_line_test_l05_quota_exceeded(): void
    {
        $json = file_get_contents(base_path('tests/Fixtures/line_test_response_lo5.json'));
        $data = json_decode($json, true);

        $result = \Modules\Provisioning\Data\LineStatusResult::fromOfV23Response($data);

        $this->assertFalse($result->success);
        $this->assertEquals('L05', $result->errorCode);
        // L05 = rate limit: NON retry (gestito da ApiQuotaManager a monte)
        $this->assertFalse($result->isRetryable);
    }
}
