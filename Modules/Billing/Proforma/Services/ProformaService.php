<?php
namespace Modules\Billing\Proforma\Services;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class ProformaService {
    public function generateProforma(object $contract, Carbon $period): object {
        $invoiceData = ['id'=>Str::uuid(),'contract_id'=>$contract->id,'customer_id'=>$contract->customer_id,'invoice_type'=>'proforma','number'=>$this->generateProformaNumber($period),'period_from'=>$period->copy()->startOfMonth()->toDateString(),'period_to'=>$period->copy()->endOfMonth()->toDateString(),'status'=>'pending','amount_cents'=>$contract->monthly_price_cents ?? 0,'currency'=>'EUR','due_date'=>$period->copy()->endOfMonth()->addDays(config('proforma.expiry_days', 30))->toDateString(),'created_at'=>now(),'updated_at'=>now()];
        DB::table('invoices')->insert($invoiceData);
        $proforma = DB::table('invoices')->find($invoiceData['id']);
        Log::info("Proforma {$proforma->number} generata per contratto {$contract->id}");
        return $proforma;
    }
    public function convertToInvoice(object $proforma): object {
        if ($proforma->invoice_type !== 'proforma') throw new \RuntimeException('Document is not a proforma');
        if ($proforma->converted_at !== null) throw new \RuntimeException('Proforma already converted');
        return DB::transaction(function () use ($proforma) {
            $invoiceId = Str::uuid();
            DB::table('invoices')->insert(['id'=>$invoiceId,'contract_id'=>$proforma->contract_id,'customer_id'=>$proforma->customer_id,'invoice_type'=>'invoice','proforma_id'=>$proforma->id,'number'=>$this->generateInvoiceNumber(),'period_from'=>$proforma->period_from,'period_to'=>$proforma->period_to,'status'=>'pending','amount_cents'=>$proforma->amount_cents,'currency'=>$proforma->currency ?? 'EUR','due_date'=>$proforma->due_date,'converted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            DB::table('invoices')->where('id', $proforma->id)->update(['status'=>'converted','converted_at'=>now(),'updated_at'=>now()]);
            $invoice = DB::table('invoices')->find($invoiceId);
            Log::info("Proforma {$proforma->number} convertita in fattura {$invoice->number}");
            return $invoice;
        });
    }
    public function expireUnpaidProformas(): int {
        $expiryDays = config('proforma.expiry_days', 30);
        $count = DB::table('invoices')->where('invoice_type','proforma')->where('status','pending')->whereDate('due_date', '<', now()->subDays($expiryDays))->update(['status'=>'expired','updated_at'=>now()]);
        Log::info("Expired {$count} unpaid proformas");
        return $count;
    }
    public function listPendingProformas(): \Illuminate\Support\Collection {
        return DB::table('invoices')->where('invoice_type','proforma')->where('status','pending')->orderBy('due_date')->get();
    }
    private function generateProformaNumber(): string {
        $year  = date('Y');
        $count = DB::table('invoices')->where('invoice_type','proforma')->whereYear('created_at', $year)->count() + 1;
        return sprintf('PRF-%s-%05d', $year, $count);
    }
    private function generateInvoiceNumber(): string {
        $year  = date('Y');
        $count = DB::table('invoices')->where('invoice_type','invoice')->whereYear('created_at', $year)->count() + 1;
        return sprintf('INV-%s-%05d', $year, $count);
    }
}
