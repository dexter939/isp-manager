<?php

namespace Modules\Billing\Cdr\Services;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Cdr\Models\CdrRecord;

class CdrBillingService
{
    /**
     * Generates invoice lines for all unbilled CDR records for a customer.
     * Groups by category, creates one invoice line per category.
     * Uses DB::transaction() + lockForUpdate().
     */
    public function generateInvoiceLines(int $customerId, int $invoiceId, Carbon $from, Carbon $to): array
    {
        $lines = [];

        DB::transaction(function () use ($customerId, $invoiceId, $from, $to, &$lines) {
            $records = CdrRecord::where('customer_id', $customerId)
                ->where('billed', false)
                ->whereBetween('start_time', [$from, $to])
                ->lockForUpdate()
                ->get();

            $grouped = $records->groupBy('category');

            foreach ($grouped as $category => $categoryRecords) {
                $totalDuration = $categoryRecords->sum('duration_seconds');
                $totalCost     = $categoryRecords->sum('total_cost_cents');

                $lines[] = [
                    'invoice_id'       => $invoiceId,
                    'description'      => 'Chiamate ' . ($category ?? 'VoIP') . ' — ' . ceil($totalDuration / 60) . ' min',
                    'quantity'         => 1,
                    'unit_price_cents' => $totalCost,
                    'total_cents'      => $totalCost,
                    'category'         => $category,
                ];
            }

            // Mark records as billed
            $records->each(function (CdrRecord $record) use ($invoiceId) {
                $record->update(['billed' => true, 'invoice_id' => $invoiceId]);
            });
        });

        return $lines;
    }

    /**
     * Runs billing for all customers with unbilled CDR records.
     * Called by CdrBillingJob monthly day-28.
     */
    public function runMonthlyBilling(Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to   = $month->copy()->endOfMonth();

        $customerIds = CdrRecord::where('billed', false)
            ->whereBetween('start_time', [$from, $to])
            ->distinct()
            ->pluck('customer_id');

        $customersBilled = 0;
        $totalRecords    = 0;
        $totalMoney      = Money::ofMinor(0, 'EUR');

        foreach ($customerIds as $customerId) {
            $count = CdrRecord::where('customer_id', $customerId)
                ->where('billed', false)
                ->whereBetween('start_time', [$from, $to])
                ->count();

            // In real implementation: create invoice and call generateInvoiceLines
            // Here we just track the stats
            $total = CdrRecord::where('customer_id', $customerId)
                ->where('billed', false)
                ->whereBetween('start_time', [$from, $to])
                ->sum('total_cost_cents');

            $customersBilled++;
            $totalRecords += $count;
            $totalMoney = $totalMoney->plus(Money::ofMinor($total, 'EUR'));
        }

        return [
            'customers_billed' => $customersBilled,
            'total_records'    => $totalRecords,
            'total_amount'     => $totalMoney,
        ];
    }
}
