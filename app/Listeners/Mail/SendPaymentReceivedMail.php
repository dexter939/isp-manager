<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Services\EmailTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Billing\Events\PaymentReceived;
use Illuminate\Support\Facades\DB;

class SendPaymentReceivedMail implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly EmailTemplateService $emailService) {}

    public function handle(PaymentReceived $event): void
    {
        $payment  = $event->payment;
        $invoice  = $event->invoice;
        $customer = DB::table('customers')->where('id', $invoice->customer_id)->first();

        if (!$customer || !$customer->email) {
            return;
        }

        $customerName = $customer->ragione_sociale
            ?: trim($customer->nome . ' ' . $customer->cognome);

        $methodLabels = [
            'bank_transfer' => 'Bonifico bancario',
            'direct_debit'  => 'RID/Addebito diretto',
            'credit_card'   => 'Carta di credito',
            'cash'          => 'Contante',
            'stripe'        => 'Carta online',
        ];

        $this->emailService->send(
            slug:      'payment_received',
            tenantId:  $invoice->tenant_id,
            toEmail:   $customer->email,
            toName:    $customerName,
            variables: [
                'customer_name'    => $customerName,
                'payment_amount'   => '€ ' . number_format($payment->amount / 100, 2, ',', '.'),
                'payment_date'     => \Carbon\Carbon::parse($payment->processed_at)->format('d/m/Y'),
                'payment_method'   => $methodLabels[$payment->method] ?? ucfirst($payment->method),
                'invoice_number'   => $invoice->invoice_number,
            ]
        );
    }
}
