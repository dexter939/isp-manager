<?php

namespace Modules\Billing\PosteItaliane\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\Invoice;
use Modules\Billing\PosteItaliane\Models\BollettinoTd896;
use Modules\Billing\PosteItaliane\Services\PosteReconciliationImporter;
use Tests\TestCase;

class PosteItalianeTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_bollettino_for_invoice(): void
    {
        $invoice = Invoice::factory()->create(['total_cents' => 5000]);
        $user    = $this->createAdminUser();

        $response = $this->actingAs($user)->postJson('/api/poste/bollettini/generate', [
            'invoice_id' => $invoice->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bollettini_td896', ['invoice_id' => $invoice->id]);
    }

    public function test_exports_prisma_format(): void
    {
        $bollettino = BollettinoTd896::factory()->create(['status' => 'generated']);
        $user       = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/api/poste/prisma/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_reconciliation_import_matches_payments(): void
    {
        $bollettino = BollettinoTd896::factory()->create([
            'numero_bollettino' => '123456789012345671',
            'status'            => 'generated',
        ]);

        $csvContent = "numero_bollettino,importo,data_pagamento\n123456789012345671,50.00,15/03/2026\n";

        $importer   = app(PosteReconciliationImporter::class);
        $result     = $importer->import($csvContent, 'test.csv');

        $this->assertEquals(1, $result->records_matched);
        $this->assertEquals('paid', $bollettino->fresh()->status);
    }
}
