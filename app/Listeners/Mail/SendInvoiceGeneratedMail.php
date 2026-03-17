<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Services\EmailTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Billing\Events\InvoiceGenerated;
use Illuminate\Support\Facades\DB;

class SendInvoiceGeneratedMail implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly EmailTemplateService $emailService) {}

    public function handle(InvoiceGenerated $event): void
    {
        $invoice  = $event->invoice;
        $customer = DB::table('customers')->where('id', $invoice->customer_id)->first();

        if (!$customer || !$customer->email) {
            return;
        }

        $customerName = $customer->ragione_sociale
            ?: trim($customer->nome . ' ' . $customer->cognome);

        $this->emailService->send(
            slug:      'invoice_generated',
            tenantId:  $invoice->tenant_id,
            toEmail:   $customer->email,
            toName:    $customerName,
            variables: [
                'customer_name'    => $customerName,
                'invoice_number'   => $invoice->invoice_number,
                'invoice_amount'   => '€ ' . number_format($invoice->total / 100, 2, ',', '.'),
                'invoice_due_date' => $invoice->due_date
                    ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y')
                    : '—',
                'invoice_period'   => $invoice->period_label ?? '—',
            ]
        );
    }
}
