<?php
namespace Modules\Billing\Proforma\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Proforma\Services\ProformaService;
use Carbon\Carbon;
class ProformaTest extends TestCase {
    use RefreshDatabase;
    private ProformaService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new ProformaService(); }
    public function test_generate_proforma_creates_proforma_type(): void {
        $contract = $this->createContract(['proforma_mode'=>true,'monthly_price_cents'=>2000]);
        $proforma = $this->service->generateProforma($contract, Carbon::now());
        $this->assertEquals('proforma', $proforma->invoice_type);
        $this->assertEquals('pending', $proforma->status);
        $this->assertStringStartsWith('PRF-', $proforma->number);
    }
    public function test_convert_proforma_creates_invoice(): void {
        $contract = $this->createContract();
        $proforma = $this->service->generateProforma($contract, Carbon::now());
        $invoice  = $this->service->convertToInvoice($proforma);
        $this->assertEquals('invoice', $invoice->invoice_type);
        $this->assertEquals($proforma->id, $invoice->proforma_id);
        $this->assertStringStartsWith('INV-', $invoice->number);
    }
    public function test_cannot_convert_already_converted_proforma(): void {
        $contract = $this->createContract();
        $proforma = $this->service->generateProforma($contract, Carbon::now());
        $this->service->convertToInvoice($proforma);
        $this->expectException(\RuntimeException::class);
        $this->service->convertToInvoice(\Illuminate\Support\Facades\DB::table('invoices')->find($proforma->id));
    }
    public function test_expire_unpaid_proformas(): void {
        $contract = $this->createContract();
        $proforma = $this->service->generateProforma($contract, Carbon::now());
        \Illuminate\Support\Facades\DB::table('invoices')->where('id', $proforma->id)->update(['due_date' => now()->subDays(35)->toDateString()]);
        $count = $this->service->expireUnpaidProformas();
        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertEquals('expired', \Illuminate\Support\Facades\DB::table('invoices')->find($proforma->id)->status);
    }
    private function createContract(array $attrs = []): object {
        $id = \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('contracts')->insert(array_merge(['id'=>$id,'customer_id'=>\Illuminate\Support\Str::uuid(),'status'=>'active','monthly_price_cents'=>2000,'proforma_mode'=>false], $attrs));
        return \Illuminate\Support\Facades\DB::table('contracts')->find($id);
    }
}
