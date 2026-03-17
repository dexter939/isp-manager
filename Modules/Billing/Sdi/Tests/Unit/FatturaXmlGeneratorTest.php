<?php

namespace Modules\Billing\Sdi\Tests\Unit;

use Modules\Billing\Models\Invoice;
use Modules\Billing\Sdi\Services\FatturaXmlGenerator;
use Tests\TestCase;

class FatturaXmlGeneratorTest extends TestCase
{
    public function test_generates_valid_fatturapa_xml(): void
    {
        config([
            'sdi.validate_xsd' => false,
            'sdi.cedente' => [
                'partita_iva'    => '12345678901',
                'codice_fiscale' => '12345678901',
                'denominazione'  => 'Test ISP Srl',
                'indirizzo'      => 'Via Roma 1',
                'cap'            => '00100',
                'comune'         => 'Roma',
                'provincia'      => 'RM',
                'nazione'        => 'IT',
                'regime_fiscale' => 'RF01',
            ],
        ]);

        $invoice = new Invoice([
            'number'         => 'FT-2024-001',
            'total_cents'    => 12200,
            'subtotal_cents' => 10000,
            'vat_cents'      => 2200,
        ]);

        $generator = new FatturaXmlGenerator();
        $xml       = $generator->generate($invoice);

        $this->assertStringContainsString('FatturaElettronica', $xml);
        $this->assertStringContainsString('ProgressivoInvio', $xml);
        $this->assertStringContainsString('CedentePrestatore', $xml);
        $this->assertStringContainsString('FPR12', $xml);
    }

    public function test_generates_correct_filename(): void
    {
        config(['sdi.cedente.partita_iva' => '12345678901']);
        $invoice   = new Invoice(['id' => 42]);
        $generator = new FatturaXmlGenerator();
        $filename  = $generator->generateFilename($invoice);

        $this->assertEquals('IT12345678901_FPR00042.xml', $filename);
    }
}
