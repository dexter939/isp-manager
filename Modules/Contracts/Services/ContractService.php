<?php

declare(strict_types=1);

namespace Modules\Contracts\Services;

use Carbon\Carbon;
use Modules\Contracts\Enums\ContractStatus;
use Modules\Contracts\Events\ContractSigned;
use Modules\Contracts\Events\ContractStatusChanged;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Models\ServicePlan;

class ContractService
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Crea un contratto in stato 'draft'.
     * I prezzi vengono copiati dal ServicePlan al momento della creazione (snapshot).
     *
     * @param array<string, mixed> $data
     */
    public function create(Customer $customer, ServicePlan $plan, array $data): Contract
    {
        $minEndDate = Carbon::parse($data['activation_date'] ?? now())
            ->addMonths($plan->min_contract_months);

        return Contract::create([
            'tenant_id'               => $customer->tenant_id,
            'customer_id'             => $customer->id,
            'service_plan_id'         => $plan->id,
            'indirizzo_installazione' => $data['indirizzo_installazione'],
            'codice_ui'               => $data['codice_ui'] ?? null,
            'id_building'             => $data['id_building'] ?? null,
            'carrier'                 => $plan->carrier->value,
            'billing_cycle'           => $data['billing_cycle'] ?? 'monthly',
            'billing_day'             => $data['billing_day'] ?? 1,

            // Snapshot prezzi — NON cambiare dopo firma
            'monthly_price'    => $plan->price_monthly,
            'activation_fee'   => $plan->activation_fee,
            'modem_fee'        => $plan->modem_fee,

            'activation_date' => $data['activation_date'] ?? null,
            'min_end_date'    => $minEndDate->toDateString(),
            'agent_id'        => $data['agent_id'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'status'          => ContractStatus::Draft->value,
        ]);
    }

    /**
     * Porta il contratto in stato 'pending_signature' e genera il PDF.
     * Chiamato prima di inviare l'OTP per la firma FEA.
     */
    public function sendForSignature(Contract $contract, PdfGeneratorService $pdfGenerator): Contract
    {
        $this->assertTransition($contract, ContractStatus::PendingSignature);

        $pdfPath = $pdfGenerator->generateContractPdf($contract);

        $contract->update([
            'status'   => ContractStatus::PendingSignature->value,
            'pdf_path' => $pdfPath,
        ]);

        return $contract->fresh();
    }

    /**
     * Attiva il contratto dopo firma FEA verificata con successo.
     * Aggiorna lo stato del cliente se era prospect.
     */
    public function activate(Contract $contract, string $signedIp, string $pdfHashPostFirma): Contract
    {
        $this->assertTransition($contract, ContractStatus::Active);

        $contract->update([
            'status'           => ContractStatus::Active->value,
            'signed_at'        => now(),
            'signed_ip'        => $signedIp,
            'pdf_hash_sha256'  => $pdfHashPostFirma,
            'activation_date'  => $contract->activation_date ?? now()->toDateString(),
        ]);

        // Attiva il cliente se era prospect
        $this->customerService->activate($contract->customer);

        event(new ContractSigned($contract));
        event(new ContractStatusChanged($contract, ContractStatus::PendingSignature, ContractStatus::Active));

        return $contract->fresh();
    }

    /**
     * Sospende il contratto (morosità grave, giorno 25 dunning).
     */
    public function suspend(Contract $contract): Contract
    {
        $this->assertTransition($contract, ContractStatus::Suspended);

        $old = $contract->status;
        $contract->update(['status' => ContractStatus::Suspended->value]);

        event(new ContractStatusChanged($contract, $old, ContractStatus::Suspended));

        return $contract->fresh();
    }

    /**
     * Riattiva un contratto sospeso (dopo pagamento ricevuto).
     */
    public function restore(Contract $contract): Contract
    {
        $this->assertTransition($contract, ContractStatus::Active);

        $old = $contract->status;
        $contract->update(['status' => ContractStatus::Active->value]);

        event(new ContractStatusChanged($contract, $old, ContractStatus::Active));

        return $contract->fresh();
    }

    /**
     * Cessa il contratto (giorno 45 dunning o richiesta cliente).
     */
    public function terminate(Contract $contract, ?string $reason = null): Contract
    {
        $this->assertTransition($contract, ContractStatus::Terminated);

        $old = $contract->status;
        $contract->update([
            'status'           => ContractStatus::Terminated->value,
            'termination_date' => now()->toDateString(),
            'notes'            => $contract->notes
                ? $contract->notes . "\n[CESSAZIONE] " . $reason
                : '[CESSAZIONE] ' . $reason,
        ]);

        event(new ContractStatusChanged($contract, $old, ContractStatus::Terminated));

        return $contract->fresh();
    }

    /**
     * Verifica che la transizione di stato sia valida.
     *
     * @throws \LogicException
     */
    private function assertTransition(Contract $contract, ContractStatus $to): void
    {
        if (!$contract->status->canTransitionTo($to)) {
            throw new \LogicException(
                "Transizione non valida: {$contract->status->value} → {$to->value} per contratto #{$contract->id}"
            );
        }
    }
}
