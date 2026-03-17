<?php

declare(strict_types=1);

namespace Modules\Provisioning\Tests\Unit;

use Modules\Provisioning\Services\XmlBuilder\OpenFiberXmlBuilder;
use Tests\TestCase;

class XmlBuilderTest extends TestCase
{
    private OpenFiberXmlBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new OpenFiberXmlBuilder();
    }

    /** @test */
    public function activation_xml_contains_required_fields(): void
    {
        $order = $this->makeOrder();
        $xml   = $this->builder->buildActivationSetup($order);

        $this->assertStringContainsString('<CODICE_ORDINE_OLO>', $xml);
        $this->assertStringContainsString('ISP-2025-001', $xml);
        $this->assertStringContainsString('<ID_BUILDING>', $xml);
        $this->assertStringContainsString('<CVLAN>', $xml);
        $this->assertStringContainsString('OLO_ActivationSetup_OpenStream', $xml);
    }

    /** @test */
    public function xml_is_valid_and_parseable(): void
    {
        $order = $this->makeOrder();
        $xml   = $this->builder->buildActivationSetup($order);

        libxml_use_internal_errors(true);
        $result = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        $this->assertNotFalse($result, 'XML non valido: ' . implode('; ', array_map(fn($e) => $e->message, $errors)));
    }

    /** @test */
    public function deactivation_xml_contains_motivo(): void
    {
        $order = $this->makeOrder();
        $xml   = $this->builder->buildDeactivation($order);

        $this->assertStringContainsString('<MOTIVO_CESSAZIONE>', $xml);
        $this->assertStringContainsString('RECESSO_CLIENTE', $xml);
        $this->assertStringContainsString('OLO_DeactivationOrder', $xml);
    }

    /** @test */
    public function reschedule_xml_contains_nuova_data(): void
    {
        $order   = $this->makeOrder();
        $newDate = \Carbon\Carbon::parse('2025-06-15');
        $xml     = $this->builder->buildReschedule($order, $newDate);

        $this->assertStringContainsString('<NUOVA_DATA>2025-06-15</NUOVA_DATA>', $xml);
        $this->assertStringContainsString('OLO_Reschedule', $xml);
    }

    /** @test */
    public function ticket_request_xml_requires_telefono_cliente(): void
    {
        $ticket = new \Modules\Provisioning\Data\TroubleTicketRequest(
            codiceOrdineOlo: 'ISP-2025-001',
            codiceOrdineOf: 'OF-12345',
            recapitoTelefonicoCliente: '+39 333 1234567',
            causaGuasto: '01',
            descTecnicaGuasto: '10',
        );

        $xml = $this->builder->buildTicketRequest($ticket);

        $this->assertStringContainsString('<RECAPITO_TELEFONICO_CLIENTE_1>+39 333 1234567</RECAPITO_TELEFONICO_CLIENTE_1>', $xml);
        $this->assertStringContainsString('<CAUSA_GUASTO>01</CAUSA_GUASTO>', $xml);
        $this->assertStringContainsString('OLO_TicketRequest', $xml);
    }

    /** @test */
    public function unsuspend_xml_sets_flag_desospensione_1(): void
    {
        $order = $this->makeOrder();
        $xml   = $this->builder->buildUnsuspend($order);

        $this->assertStringContainsString('<FLAG_DESOSPENSIONE>1</FLAG_DESOSPENSIONE>', $xml);
        $this->assertStringContainsString('OLO_StatusUpdate', $xml);
    }

    private function makeOrder(): \Modules\Provisioning\Models\CarrierOrder
    {
        $order = $this->createMock(\Modules\Provisioning\Models\CarrierOrder::class);
        $order->codice_ordine_olo = 'ISP-2025-001';
        $order->codice_ordine_of  = 'OF-99999';
        $order->cvlan             = '200';

        $customer = $this->createMock(\Modules\Contracts\Models\Customer::class);
        $customer->method('getAttribute')->willReturnMap([
            ['full_name', 'Mario Rossi'],
            ['cellulare', '+39 333 1234567'],
            ['email', 'mario@example.com'],
        ]);

        $plan = $this->createMock(\Modules\Contracts\Models\ServicePlan::class);
        $plan->method('getAttribute')->willReturnMap([['technology', 'FTTH']]);

        $contract = $this->createMock(\Modules\Contracts\Models\Contract::class);
        $contract->id_building = 'OF-BLD-123';
        $contract->method('getAttribute')->willReturnMap([
            ['id_building', 'OF-BLD-123'],
            ['indirizzo_installazione', ['via' => 'Via Roma', 'civico' => '1', 'comune' => 'Milano', 'provincia' => 'MI', 'cap' => '20100']],
            ['customer', $customer],
            ['servicePlan', $plan],
        ]);

        $order->method('getAttribute')->willReturnMap([
            ['codice_ordine_olo', 'ISP-2025-001'],
            ['codice_ordine_of', 'OF-99999'],
            ['cvlan', '200'],
            ['contract', $contract],
        ]);

        return $order;
    }
}
