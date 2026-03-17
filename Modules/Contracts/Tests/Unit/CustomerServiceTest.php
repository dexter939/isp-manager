<?php

declare(strict_types=1);

namespace Modules\Contracts\Tests\Unit;

use Modules\Contracts\Enums\CustomerStatus;
use Modules\Contracts\Enums\CustomerType;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Services\CustomerService;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    private CustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CustomerService();
    }

    /** @test */
    public function it_validates_a_valid_codice_fiscale(): void
    {
        // CF valido generato con algoritmo ufficiale
        $data = $this->makePrivatoData(['codice_fiscale' => 'RSSMRA80A01H501T']);

        $customer = $this->service->create($data, tenantId: 1);

        $this->assertInstanceOf(Customer::class, $customer);
    }

    /** @test */
    public function it_rejects_invalid_codice_fiscale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/codice fiscale/i');

        $data = $this->makePrivatoData(['codice_fiscale' => 'ZZZZZZ00Z00Z000Z']);
        $this->service->create($data, tenantId: 1);
    }

    /** @test */
    public function it_validates_a_valid_partita_iva(): void
    {
        $data = $this->makeAziendaData(['piva' => '02182030391']); // P.IVA valida

        $customer = $this->service->create($data, tenantId: 1);

        $this->assertInstanceOf(Customer::class, $customer);
    }

    /** @test */
    public function it_rejects_invalid_partita_iva(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = $this->makeAziendaData(['piva' => '12345678900']);
        $this->service->create($data, tenantId: 1);
    }

    /** @test */
    public function it_sets_status_to_prospect_on_creation(): void
    {
        $customer = $this->service->create($this->makePrivatoData(), tenantId: 1);

        $this->assertEquals(CustomerStatus::Prospect, $customer->status);
    }

    /** @test */
    public function it_activates_a_prospect_customer(): void
    {
        $customer = Customer::factory()->prospect()->create();

        $this->service->activate($customer);

        $this->assertEquals(CustomerStatus::Active, $customer->fresh()->status);
    }

    /** @test */
    public function it_does_not_downgrade_an_active_customer(): void
    {
        $customer = Customer::factory()->active()->create();

        $this->service->activate($customer); // no-op

        $this->assertEquals(CustomerStatus::Active, $customer->fresh()->status);
    }

    /** @test */
    public function it_requires_piva_for_azienda(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = $this->makeAziendaData(['piva' => '12345678900']);
        $this->service->create($data, tenantId: 1);
    }

    // ---- Helper factories ----

    private function makePrivatoData(array $overrides = []): array
    {
        return array_merge([
            'type'             => 'privato',
            'nome'             => 'Mario',
            'cognome'          => 'Rossi',
            'codice_fiscale'   => null,
            'email'            => 'mario.rossi@example.com',
            'cellulare'        => '+39 333 1234567',
            'payment_method'   => 'bonifico',
            'indirizzo_fatturazione' => [
                'via'      => 'Via Roma',
                'civico'   => '1',
                'comune'   => 'Milano',
                'provincia'=> 'MI',
                'cap'      => '20100',
            ],
        ], $overrides);
    }

    private function makeAziendaData(array $overrides = []): array
    {
        return array_merge([
            'type'             => 'azienda',
            'ragione_sociale'  => 'Acme Srl',
            'codice_fiscale'   => null,
            'piva'             => '02182030391',
            'email'            => 'info@acme.it',
            'cellulare'        => '+39 02 1234567',
            'payment_method'   => 'bonifico',
            'indirizzo_fatturazione' => [
                'via'      => 'Corso Venezia',
                'civico'   => '10',
                'comune'   => 'Milano',
                'provincia'=> 'MI',
                'cap'      => '20121',
            ],
        ], $overrides);
    }
}
