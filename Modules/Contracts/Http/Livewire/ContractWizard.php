<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Contracts\Enums\BillingCycle;
use Modules\Contracts\Enums\CustomerType;
use Modules\Contracts\Enums\PaymentMethod;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Models\ServicePlan;
use Modules\Contracts\Services\ContractService;
use Modules\Contracts\Services\CustomerService;
use Modules\Contracts\Services\FEAService;
use Modules\Contracts\Services\PdfGeneratorService;

/**
 * Wizard contratto a 6 step.
 *
 * Step 1 — Selezione/Creazione Cliente
 * Step 2 — Indirizzo di Installazione
 * Step 3 — Selezione Piano e Offerta
 * Step 4 — Metodo di Pagamento
 * Step 5 — Riepilogo + Anteprima PDF
 * Step 6 — Firma FEA (OTP)
 */
class ContractWizard extends Component
{
    // ---- Step corrente ----
    public int $step = 1;
    public const TOTAL_STEPS = 6;

    // ---- Step 1: Cliente ----
    public ?int $customerId = null;
    public string $customerType = 'privato';
    public string $nome = '';
    public string $cognome = '';
    public string $ragioneSociale = '';
    public string $codiceFiscale = '';
    public string $piva = '';
    public string $email = '';
    public string $pec = '';
    public string $cellulare = '';
    public string $telefono = '';

    // ---- Step 2: Indirizzo installazione ----
    public string $via = '';
    public string $civico = '';
    public string $comune = '';
    public string $provincia = '';
    public string $cap = '';
    public string $scala = '';
    public string $piano = '';
    public string $interno = '';

    // ---- Step 3: Piano ----
    public ?int $servicePlanId = null;

    // ---- Step 4: Pagamento ----
    public string $paymentMethod = 'bonifico';
    public string $iban = '';
    public string $billingCycle = 'monthly';
    public int $billingDay = 1;

    // ---- Step 5/6: Contratto creato ----
    public ?int $contractId = null;
    public string $otpSentTo = '';
    public string $otpChannel = 'sms';
    public string $otp = '';

    // ---- Stato UI ----
    public ?string $errorMessage = null;
    public bool $isLoading = false;
    public bool $signatureSuccess = false;

    public function mount(): void
    {
        // Nessuna inizializzazione speciale necessaria
    }

    // ---- Navigazione step ----

    public function nextStep(): void
    {
        $this->errorMessage = null;
        $this->validateCurrentStep();
        $this->step++;
    }

    public function previousStep(): void
    {
        $this->errorMessage = null;
        $this->step = max(1, $this->step - 1);
    }

    public function goToStep(int $target): void
    {
        if ($target < $this->step) {
            $this->step = $target;
        }
    }

    // ---- Computed properties ----

    #[Computed]
    public function availablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return ServicePlan::active()->public()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get();
    }

    #[Computed]
    public function selectedPlan(): ?ServicePlan
    {
        return $this->servicePlanId
            ? ServicePlan::find($this->servicePlanId)
            : null;
    }

    #[Computed]
    public function selectedCustomer(): ?Customer
    {
        return $this->customerId
            ? Customer::find($this->customerId)
            : null;
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::cases();
    }

    #[Computed]
    public function billingCycles(): array
    {
        return BillingCycle::cases();
    }

    #[Computed]
    public function monthlyTotal(): float
    {
        return $this->selectedPlan
            ? (float) $this->selectedPlan->price_monthly * 1.22
            : 0.0;
    }

    // ---- Azioni step ----

    /** Step 1: cerca clienti esistenti (live search) */
    public function searchCustomer(string $query): void
    {
        // Emit per la view (lista suggerimenti)
        $this->dispatch('customer-search-results', query: $query);
    }

    public function selectExistingCustomer(int $id): void
    {
        $this->customerId = $id;
        $customer = Customer::findOrFail($id);
        $this->customerType   = $customer->type->value;
        $this->nome           = $customer->nome ?? '';
        $this->cognome        = $customer->cognome ?? '';
        $this->ragioneSociale = $customer->ragione_sociale ?? '';
        $this->codiceFiscale  = $customer->codice_fiscale ?? '';
        $this->piva           = $customer->piva ?? '';
        $this->email          = $customer->email ?? '';
        $this->pec            = $customer->pec ?? '';
        $this->cellulare      = $customer->cellulare ?? '';
        $this->telefono       = $customer->telefono ?? '';
    }

    /** Step 5: crea contratto e genera PDF */
    public function prepareContract(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $customer    = $this->resolveOrCreateCustomer();
            $plan        = ServicePlan::findOrFail($this->servicePlanId);
            $contract    = app(ContractService::class)->create($customer, $plan, [
                'indirizzo_installazione' => [
                    'via'      => $this->via,
                    'civico'   => $this->civico,
                    'comune'   => $this->comune,
                    'provincia'=> $this->provincia,
                    'cap'      => $this->cap,
                    'scala'    => $this->scala ?: null,
                    'piano'    => $this->piano ?: null,
                    'interno'  => $this->interno ?: null,
                ],
                'billing_cycle' => $this->billingCycle,
                'billing_day'   => $this->billingDay,
                'agent_id'      => auth()->id(),
            ]);

            $this->contractId = $contract->id;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /** Step 6: invia OTP per firma FEA */
    public function sendOtp(): void
    {
        $this->isLoading    = true;
        $this->errorMessage = null;

        try {
            $contract  = \Modules\Contracts\Models\Contract::findOrFail($this->contractId);
            $pdfGen    = app(PdfGeneratorService::class);

            // Genera PDF + porta in pending_signature
            app(ContractService::class)->sendForSignature($contract, $pdfGen);

            // Invia OTP
            $signature       = app(FEAService::class)->sendOtp($contract->fresh(), $this->otpChannel);
            $this->otpSentTo = $signature->otp_sent_to;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /** Step 6: verifica OTP e firma */
    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ], [
            'otp.required' => 'Inserisci il codice OTP.',
            'otp.size'     => 'Il codice OTP deve essere di 6 cifre.',
            'otp.regex'    => 'Il codice deve contenere solo cifre.',
        ]);

        $this->isLoading    = true;
        $this->errorMessage = null;

        try {
            $contract = \Modules\Contracts\Models\Contract::findOrFail($this->contractId);

            app(FEAService::class)->verifyAndSign(
                contract: $contract,
                otp: $this->otp,
                clientIp: request()->ip(),
                userAgent: request()->userAgent() ?? '',
            );

            $this->signatureSuccess = true;
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    // ---- Render ----

    public function render(): \Illuminate\View\View
    {
        return view('contracts::livewire.wizard.contract-wizard');
    }

    // ---- Private helpers ----

    private function validateCurrentStep(): void
    {
        match ($this->step) {
            1 => $this->validateStep1(),
            2 => $this->validateStep2(),
            3 => $this->validateStep3(),
            4 => $this->validateStep4(),
            default => null,
        };
    }

    private function validateStep1(): void
    {
        $rules = [
            'customerType' => ['required', 'in:privato,azienda'],
            'email'        => ['required', 'email'],
            'cellulare'    => ['required', 'string'],
        ];

        if ($this->customerType === 'privato') {
            $rules['nome']    = ['required', 'string'];
            $rules['cognome'] = ['required', 'string'];
        } else {
            $rules['ragioneSociale'] = ['required', 'string'];
        }

        $this->validate($rules);
    }

    private function validateStep2(): void
    {
        $this->validate([
            'via'       => ['required', 'string'],
            'civico'    => ['required', 'string'],
            'comune'    => ['required', 'string'],
            'provincia' => ['required', 'string', 'size:2'],
            'cap'       => ['required', 'string', 'size:5'],
        ]);
    }

    private function validateStep3(): void
    {
        $this->validate([
            'servicePlanId' => ['required', 'integer', 'exists:service_plans,id'],
        ]);
    }

    private function validateStep4(): void
    {
        $rules = [
            'paymentMethod' => ['required', 'in:sdd,carta,bonifico,contanti'],
            'billingCycle'  => ['required', 'in:monthly,annual'],
            'billingDay'    => ['required', 'integer', 'min:1', 'max:28'],
        ];

        if ($this->paymentMethod === 'sdd') {
            $rules['iban'] = ['required', 'string', 'max:34'];
        }

        $this->validate($rules);
    }

    private function resolveOrCreateCustomer(): Customer
    {
        if ($this->customerId) {
            return Customer::findOrFail($this->customerId);
        }

        $data = [
            'type'          => $this->customerType,
            'nome'          => $this->nome ?: null,
            'cognome'       => $this->cognome ?: null,
            'ragione_sociale' => $this->ragioneSociale ?: null,
            'codice_fiscale'  => $this->codiceFiscale ?: null,
            'piva'            => $this->piva ?: null,
            'email'           => $this->email,
            'pec'             => $this->pec ?: null,
            'cellulare'       => $this->cellulare,
            'telefono'        => $this->telefono ?: null,
            'payment_method'  => $this->paymentMethod,
            'iban'            => $this->iban ?: null,
            'indirizzo_fatturazione' => [
                'via'      => $this->via,
                'civico'   => $this->civico,
                'comune'   => $this->comune,
                'provincia'=> $this->provincia,
                'cap'      => $this->cap,
            ],
        ];

        $customer = app(CustomerService::class)->create($data, auth()->user()->tenant_id);
        $this->customerId = $customer->id;

        return $customer;
    }
}
