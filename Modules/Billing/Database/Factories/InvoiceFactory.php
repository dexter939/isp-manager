<?php

declare(strict_types=1);

namespace Modules\Billing\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Enums\InvoiceType;
use Modules\Billing\Models\Invoice;
use Modules\Contracts\Database\Factories\ContractFactory;
use Modules\Contracts\Database\Factories\CustomerFactory;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal  = $this->faker->randomFloat(2, 15.00, 80.00);
        $taxRate   = 22.00;
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total     = round($subtotal + $taxAmount, 2);

        $from = now()->startOfMonth()->subMonth();
        $to   = $from->copy()->endOfMonth();

        static $progressive = 1;

        return [
            'tenant_id'      => Tenant::factory(),
            'customer_id'    => Customer::factory(),
            'contract_id'    => Contract::factory(),
            'number'         => 'FT-' . now()->year . '-' . str_pad($progressive++, 6, '0', STR_PAD_LEFT),
            'sdi_progressive'=> null,
            'type'           => InvoiceType::Ordinary->value,
            'period_from'    => $from->toDateString(),
            'period_to'      => $to->toDateString(),
            'issue_date'     => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'subtotal'       => $subtotal,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'stamp_duty'     => 0.00,
            'total'          => $total,
            'status'         => InvoiceStatus::Issued->value,
            'payment_method' => 'bonifico',
            'notes'          => null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status'  => InvoiceStatus::Paid->value,
            'paid_at' => now()->subDays(5),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'   => InvoiceStatus::Overdue->value,
            'due_date' => now()->subDays(15)->toDateString(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => InvoiceStatus::Draft->value]);
    }

    public function issued(): static
    {
        return $this->state(['status' => InvoiceStatus::Issued->value]);
    }
}
