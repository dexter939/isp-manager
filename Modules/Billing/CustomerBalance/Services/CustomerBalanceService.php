<?php
namespace Modules\Billing\CustomerBalance\Services;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Billing\CustomerBalance\Models\CustomerBalanceMovement;
class CustomerBalanceService {
    public function getBalance(object $customer): Money {
        return Money::ofMinor($customer->balance_amount, $customer->balance_currency ?? 'EUR');
    }
    public function applyPayment(object $customer, object $payment): void {
        DB::transaction(function () use ($customer, $payment) {
            $locked = DB::table('customers')->where('id', $customer->id)->lockForUpdate()->first();
            $before = $locked->balance_amount;
            $after  = $before + $payment->amount_cents;
            DB::table('customers')->where('id', $customer->id)->update(['balance_amount' => $after]);
            CustomerBalanceMovement::create(['customer_id'=>$customer->id,'type'=>'payment','amount_amount'=>$payment->amount_cents,'amount_currency'=>$customer->balance_currency ?? 'EUR','balance_before'=>$before,'balance_after'=>$after,'reference_id'=>$payment->id,'description'=>"Pagamento {$payment->reference}",'created_by'=>auth()->id()]);
        });
    }
    public function applyInvoice(object $customer, object $invoice): void {
        DB::transaction(function () use ($customer, $invoice) {
            $locked = DB::table('customers')->where('id', $customer->id)->lockForUpdate()->first();
            $before = $locked->balance_amount;
            $after  = $before - $invoice->amount_cents;
            DB::table('customers')->where('id', $customer->id)->update(['balance_amount' => $after]);
            CustomerBalanceMovement::create(['customer_id'=>$customer->id,'type'=>'invoice','amount_amount'=>-$invoice->amount_cents,'amount_currency'=>$customer->balance_currency ?? 'EUR','balance_before'=>$before,'balance_after'=>$after,'reference_id'=>$invoice->id,'description'=>"Fattura {$invoice->number}",'created_by'=>null]);
            // If credit is sufficient, mark invoice as paid
            if ($after >= 0 && $before < 0 === false) {
                DB::table('invoices')->where('id', $invoice->id)->update(['status' => 'paid', 'paid_at' => now()]);
            }
        });
    }
    public function setOpeningBalance(object $customer, Money $amount, Carbon $date, string $note): void {
        DB::transaction(function () use ($customer, $amount, $date, $note) {
            $locked   = DB::table('customers')->where('id', $customer->id)->lockForUpdate()->first();
            $before   = $locked->balance_amount;
            $cents    = $amount->getMinorAmount()->toInt();
            $after    = $before + $cents;
            DB::table('customers')->where('id', $customer->id)->update(['balance_amount'=>$after,'opening_balance_amount'=>$cents,'opening_balance_date'=>$date->toDateString(),'opening_balance_note'=>$note]);
            CustomerBalanceMovement::create(['customer_id'=>$customer->id,'type'=>'opening_balance','amount_amount'=>$cents,'amount_currency'=>$amount->getCurrency()->getCurrencyCode(),'balance_before'=>$before,'balance_after'=>$after,'reference_id'=>null,'description'=>"Saldo di apertura: {$note}",'created_by'=>auth()->id()]);
        });
    }
    public function getStatement(object $customer, Carbon $from, Carbon $to): array {
        $movements = CustomerBalanceMovement::where('customer_id', $customer->id)->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->orderBy('created_at')->get();
        $openingBalance = $movements->isEmpty() ? $customer->balance_amount : ($movements->first()->balance_before ?? 0);
        return ['customer_id'=>$customer->id,'from'=>$from->toDateString(),'to'=>$to->toDateString(),'opening_balance_cents'=>$openingBalance,'closing_balance_cents'=>$customer->balance_amount,'movements'=>$movements];
    }
    public function manualAdjustment(object $customer, int $cents, string $description): void {
        DB::transaction(function () use ($customer, $cents, $description) {
            $locked = DB::table('customers')->where('id', $customer->id)->lockForUpdate()->first();
            $before = $locked->balance_amount;
            $after  = $before + $cents;
            DB::table('customers')->where('id', $customer->id)->update(['balance_amount' => $after]);
            CustomerBalanceMovement::create(['customer_id'=>$customer->id,'type'=>'adjustment','amount_amount'=>$cents,'amount_currency'=>$customer->balance_currency ?? 'EUR','balance_before'=>$before,'balance_after'=>$after,'reference_id'=>null,'description'=>$description,'created_by'=>auth()->id()]);
        });
    }
}
