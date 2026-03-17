<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Services\EmailTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contracts\Events\ContractSigned;
use Illuminate\Support\Facades\DB;

class SendContractSignedMail implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly EmailTemplateService $emailService) {}

    public function handle(ContractSigned $event): void
    {
        $contract = $event->contract;
        $customer = DB::table('customers')->where('id', $contract->customer_id)->first();

        if (!$customer || !$customer->email) {
            return;
        }

        $customerName = $customer->ragione_sociale
            ?: trim($customer->nome . ' ' . $customer->cognome);

        $plan = DB::table('service_plans')->where('id', $contract->service_plan_id)->first();

        $this->emailService->send(
            slug:      'contract_signed',
            tenantId:  $contract->tenant_id,
            toEmail:   $customer->email,
            toName:    $customerName,
            variables: [
                'customer_name'    => $customerName,
                'contract_number'  => $contract->contract_number,
                'plan_name'        => $plan?->name ?? '—',
                'activation_date'  => $contract->activation_date
                    ? \Carbon\Carbon::parse($contract->activation_date)->format('d/m/Y')
                    : now()->format('d/m/Y'),
            ]
        );
    }
}
