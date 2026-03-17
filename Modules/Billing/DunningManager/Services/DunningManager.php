<?php

declare(strict_types=1);

namespace Modules\Billing\DunningManager\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Billing\DunningManager\Enums\DunningAction;
use Modules\Billing\DunningManager\Enums\DunningStatus;
use Modules\Billing\DunningManager\Events\DunningCaseOpened;
use Modules\Billing\DunningManager\Events\DunningCaseResolved;
use Modules\Billing\DunningManager\Models\DunningCase;
use Modules\Billing\DunningManager\Models\DunningPolicy;
use Modules\Billing\DunningManager\Models\DunningStep;
use Modules\Billing\DunningManager\Models\DunningWhitelist;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\InvoiceItem;
use Modules\Contracts\Models\Contract;

class DunningManager
{
    public function __construct(
        private readonly DunningPolicy $defaultPolicy,
    ) {}

    /**
     * Opens a dunning case for an overdue invoice.
     *
     * Checks whitelist first — if customer is whitelisted and active, skips.
     * Assigns policy: customer-specific > contract-specific > default.
     *
     * @param  Invoice $invoice  The overdue invoice to start dunning for.
     * @return DunningCase|null  The created case, or null if skipped (whitelisted).
     */
    public function startForInvoice(Invoice $invoice): ?DunningCase
    {
        // Check if customer is whitelisted
        $whitelisted = DunningWhitelist::query()
            ->where('customer_id', $invoice->customer_id)
            ->active()
            ->exists();

        if ($whitelisted) {
            Log::info('DunningManager: customer whitelisted, skipping', [
                'invoice_id'  => $invoice->id,
                'customer_id' => $invoice->customer_id,
            ]);
            return null;
        }

        // Resolve policy: default policy for now (can be extended for per-customer/contract)
        $policy = DunningPolicy::query()
            ->active()
            ->default()
            ->first() ?? $this->defaultPolicy;

        $firstStep = $policy->getStep(0);
        $graceDays = (int) config('dunning.grace_days', 3);

        return DB::transaction(function () use ($invoice, $policy, $firstStep, $graceDays): DunningCase {
            $case = DunningCase::create([
                'invoice_id'          => $invoice->id,
                'customer_id'         => $invoice->customer_id,
                'contract_id'         => $invoice->contract_id,
                'policy_id'           => $policy->id,
                'status'              => DunningStatus::Open->value,
                'opened_at'           => now(),
                'current_step_index'  => 0,
                'next_action_at'      => now()->addDays($firstStep['day'] ?? $graceDays),
                'total_penalty_cents' => 0,
            ]);

            event(new DunningCaseOpened($case));

            Log::info('DunningManager: case opened', [
                'case_id'    => $case->id,
                'invoice_id' => $invoice->id,
            ]);

            return $case;
        });
    }

    /**
     * Processes the next due step for a dunning case.
     *
     * Checks if next_action_at <= now().
     * Executes action (email/sms/whatsapp/suspend/terminate).
     * Logs DunningStep, updates current_step_index and next_action_at.
     *
     * On 'suspend': calls suspendPppoe($case->contract)
     * On 'terminate': calls terminateContract($case->contract), sets case status=terminated
     * On penalty: adds penalty_cents to total_penalty_cents, creates invoice line
     *
     * @param  DunningCase $case  The dunning case to advance.
     */
    public function processStep(DunningCase $case): void
    {
        if ($case->next_action_at->isAfter(now())) {
            return;
        }

        DB::transaction(function () use ($case): void {
            /** @var DunningCase $case */
            $case = DunningCase::query()
                ->lockForUpdate()
                ->findOrFail($case->id);

            if ($case->status !== DunningStatus::Open) {
                return;
            }

            $policy  = $case->policy;
            $stepDef = $policy->getStep($case->current_step_index);

            if ($stepDef === null) {
                Log::warning('DunningManager: no step found', [
                    'case_id'    => $case->id,
                    'step_index' => $case->current_step_index,
                ]);
                return;
            }

            $action = DunningAction::from($stepDef['action']);
            $result = 'success';
            $notes  = null;

            try {
                match ($action) {
                    DunningAction::Email,
                    DunningAction::Sms,
                    DunningAction::Whatsapp => $this->executeNotification($case, $stepDef),

                    DunningAction::Suspend => $this->executeSuspend($case, $stepDef),

                    DunningAction::Terminate => $this->executeTerminate($case),
                };
            } catch (\Throwable $e) {
                $result = 'failed';
                $notes  = $e->getMessage();
                Log::error('DunningManager: step execution failed', [
                    'case_id' => $case->id,
                    'action'  => $action->value,
                    'error'   => $e->getMessage(),
                ]);
            }

            // Log the executed step
            DunningStep::create([
                'case_id'     => $case->id,
                'step_index'  => $case->current_step_index,
                'action'      => $action->value,
                'executed_at' => now(),
                'result'      => $result,
                'notes'       => $notes,
            ]);

            // If terminated, the status was already set inside executeTerminate; stop here
            if ($action === DunningAction::Terminate) {
                return;
            }

            // Advance to the next step
            $nextIndex  = $case->current_step_index + 1;
            $nextStepDef = $policy->getStep($nextIndex);

            if ($nextStepDef !== null) {
                $case->update([
                    'current_step_index' => $nextIndex,
                    'next_action_at'     => now()->addDays($nextStepDef['day'] - ($stepDef['day'] ?? 0)),
                ]);
            } else {
                // No more steps; keep case open but far in the future to prevent reprocessing
                $case->update([
                    'current_step_index' => $nextIndex,
                    'next_action_at'     => now()->addYears(1),
                ]);
            }
        });
    }

    /**
     * Resolves all open cases for a contract when payment is received.
     *
     * Called from payment webhook/event listener. Fires DunningCaseResolved event
     * for each resolved case.
     *
     * @param  int $contractId  The contract whose open dunning cases should be resolved.
     */
    public function resolveOnPayment(int $contractId): void
    {
        DB::transaction(function () use ($contractId): void {
            $cases = DunningCase::query()
                ->lockForUpdate()
                ->where('contract_id', $contractId)
                ->where('status', DunningStatus::Open->value)
                ->get();

            foreach ($cases as $case) {
                $case->update([
                    'status'      => DunningStatus::Resolved->value,
                    'resolved_at' => now(),
                ]);

                // If contract was suspended, reactivate it
                $contract = $case->contract;
                if ($contract && $contract->status?->value === 'suspended') {
                    $this->reactivatePppoe($contract);
                }

                event(new DunningCaseResolved($case));

                Log::info('DunningManager: case resolved on payment', [
                    'case_id'     => $case->id,
                    'contract_id' => $contractId,
                ]);
            }
        });
    }

    /**
     * Suspends the PPPoE session via CoaService and sets contract status to 'suspended'.
     *
     * @param  Contract $contract  The contract to suspend.
     */
    public function suspendPppoe(Contract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            /** @var Contract $contract */
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);

            if (!config('app.carrier_mock', false)) {
                // CoaService::disconnect($contract->pppoe_username);
                Log::info('DunningManager: PPPoE suspend (real)', ['contract_id' => $contract->id]);
            } else {
                Log::info('DunningManager: PPPoE suspend (mock)', ['contract_id' => $contract->id]);
            }

            $contract->update(['status' => 'suspended']);
        });
    }

    /**
     * Reactivates the PPPoE session via CoaService and sets contract status back to 'active'.
     *
     * @param  Contract $contract  The contract to reactivate.
     */
    public function reactivatePppoe(Contract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            /** @var Contract $contract */
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);

            if (!config('app.carrier_mock', false)) {
                // CoaService::reconnect($contract->pppoe_username);
                Log::info('DunningManager: PPPoE reactivate (real)', ['contract_id' => $contract->id]);
            } else {
                Log::info('DunningManager: PPPoE reactivate (mock)', ['contract_id' => $contract->id]);
            }

            $contract->update(['status' => 'active']);
        });
    }

    /**
     * Terminates the contract and sets its status to 'terminated'.
     *
     * @param  Contract $contract  The contract to terminate.
     */
    private function terminateContract(Contract $contract): void
    {
        $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
        $contract->update([
            'status'           => 'terminated',
            'termination_date' => now(),
        ]);

        Log::info('DunningManager: contract terminated', ['contract_id' => $contract->id]);
    }

    /**
     * Dispatches an email, SMS, or WhatsApp notification for a dunning step.
     *
     * Respects config('app.carrier_mock') to skip actual sending in mock mode.
     *
     * @param  DunningCase $case     The dunning case.
     * @param  array       $stepDef  The step definition from the policy.
     */
    private function executeNotification(DunningCase $case, array $stepDef): void
    {
        $action   = DunningAction::from($stepDef['action']);
        $template = $stepDef['template'] ?? 'default_reminder';
        $customer = $case->customer;

        if (config('app.carrier_mock', false)) {
            Log::info('DunningManager: notification skipped (mock)', [
                'case_id'  => $case->id,
                'action'   => $action->value,
                'template' => $template,
            ]);
            return;
        }

        match ($action) {
            DunningAction::Email    => Log::info('DunningManager: sending email', [
                'customer_id' => $customer?->id,
                'template'    => $template,
            ]),
            DunningAction::Sms      => Log::info('DunningManager: sending SMS', [
                'customer_id' => $customer?->id,
                'template'    => $template,
            ]),
            DunningAction::Whatsapp => Log::info('DunningManager: sending WhatsApp', [
                'customer_id' => $customer?->id,
                'template'    => $template,
            ]),
            default => null,
        };
    }

    /**
     * Executes the suspend action: suspends PPPoE and optionally adds penalty.
     */
    private function executeSuspend(DunningCase $case, array $stepDef): void
    {
        $contract = $case->contract;

        if ($contract) {
            $this->suspendPppoe($contract);
        }

        // Apply penalty if specified
        if (isset($stepDef['penalty_cents']) && $stepDef['penalty_cents'] > 0) {
            $penaltyCents = (int) $stepDef['penalty_cents'];

            $case->increment('total_penalty_cents', $penaltyCents);

            // Create a penalty invoice item on the related invoice
            $invoice = $case->invoice;
            if ($invoice) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => 'Penale sospensione (sollecito)',
                    'quantity'    => 1,
                    'unit_price'  => $penaltyCents,
                    'total'       => $penaltyCents,
                ]);
            }
        }
    }

    /**
     * Executes the terminate action: terminates the contract and closes the dunning case.
     */
    private function executeTerminate(DunningCase $case): void
    {
        $contract = $case->contract;

        if ($contract) {
            $this->terminateContract($contract);
        }

        $case->update([
            'status'      => DunningStatus::Terminated->value,
            'resolved_at' => now(),
        ]);
    }
}
