<?php

declare(strict_types=1);

namespace Modules\Contracts\WizardMobile\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\WizardMobile\Enums\WizardStatus;
use Modules\Contracts\WizardMobile\Enums\WizardStep;
use Modules\Contracts\WizardMobile\Models\ContractWizardSession;
use RuntimeException;

class WizardSessionService
{
    public function __construct()
    {
    }

    /**
     * Creates a new wizard session.
     *
     * Saves to Redis with TTL 24h (key: wizard:session:{uuid}).
     * Returns ContractWizardSession.
     *
     * @param int|null $agentId    User ID of the agent creating the contract
     * @param int|null $customerId User ID of the existing customer or null for new
     */
    public function create(?int $agentId = null, ?int $customerId = null): ContractWizardSession
    {
        $ttlHours = (int) config('wizard-mobile.session_ttl_hours', 24);
        $now      = Carbon::now();

        $session = ContractWizardSession::create([
            'uuid'             => (string) Str::uuid(),
            'agent_id'         => $agentId,
            'customer_id'      => $customerId,
            'current_step'     => 0,
            'step_data'        => [],
            'status'           => WizardStatus::InProgress->value,
            'otp_verified'     => false,
            'started_at'       => $now,
            'last_activity_at' => $now,
            'expires_at'       => $now->copy()->addHours($ttlHours),
        ]);

        $this->persistToRedis($session);

        return $session;
    }

    /**
     * Saves step data for a session.
     *
     * Validates step data per step definition (required fields check).
     * Merges into session step_data jsonb.
     * Autosaves to Redis (resets TTL to 24h).
     * Advances current_step if data is valid.
     *
     * @param array<string, mixed> $data
     */
    public function saveStep(ContractWizardSession $session, int $step, array $data): ContractWizardSession
    {
        $this->ensureSessionIsActive($session);

        $wizardStep = WizardStep::from($step);

        $this->validateStepData($wizardStep, $data);

        return DB::transaction(function () use ($session, $wizardStep, $data): ContractWizardSession {
            /** @var ContractWizardSession $fresh */
            $fresh = ContractWizardSession::lockForUpdate()->findOrFail($session->id);

            $stepData              = $fresh->step_data ?? [];
            $stepData[$wizardStep->name] = $data;

            // Advance step if current step is being completed
            $nextStep = $fresh->current_step;
            if ($wizardStep->value >= $fresh->current_step) {
                $nextStep = min($wizardStep->value + 1, WizardStep::Otp->value);
            }

            $fresh->update([
                'step_data'        => $stepData,
                'current_step'     => $nextStep,
                'last_activity_at' => Carbon::now(),
            ]);

            $this->persistToRedis($fresh);

            return $fresh;
        });
    }

    /**
     * Sends OTP to customer phone (from step_data.Cliente.telefono).
     *
     * Stores hashed OTP + expiry in session.
     * Uses config('app.carrier_mock') to skip actual SMS in mock mode.
     *
     * @throws RuntimeException If phone number is not available in session data
     */
    public function sendOtp(ContractWizardSession $session): void
    {
        $this->ensureSessionIsActive($session);

        $clienteData = $session->step_data['Cliente'] ?? [];
        $telefono    = $clienteData['telefono'] ?? null;

        if (empty($telefono)) {
            throw new RuntimeException('Numero di telefono non disponibile nei dati del cliente.');
        }

        $otpLength = (int) config('wizard-mobile.otp_length', 6);
        $otpCode   = $this->generateOtpCode($otpLength);
        $expiresAt = Carbon::now()->addMinutes(
            (int) config('wizard-mobile.otp_expires_minutes', 10)
        );

        DB::transaction(function () use ($session, $otpCode, $expiresAt): void {
            /** @var ContractWizardSession $fresh */
            $fresh = ContractWizardSession::lockForUpdate()->findOrFail($session->id);

            $fresh->update([
                'otp_code'       => Hash::make($otpCode),
                'otp_expires_at' => $expiresAt,
                'otp_verified'   => false,
            ]);

            $this->persistToRedis($fresh);
        });

        // Send SMS (skip if in mock/carrier_mock mode)
        if (! config('app.carrier_mock', false)) {
            $this->dispatchSms($telefono, $otpCode);
        }
    }

    /**
     * Verifies OTP entered by customer.
     *
     * Sets otp_verified = true if correct and not expired.
     *
     * @param string $otp The OTP code provided by the customer
     * @return bool True if OTP is valid and not expired, false otherwise
     */
    public function verifyOtp(ContractWizardSession $session, string $otp): bool
    {
        $this->ensureSessionIsActive($session);

        if ($session->isOtpExpired()) {
            return false;
        }

        if (! Hash::check($otp, (string) $session->otp_code)) {
            return false;
        }

        DB::transaction(function () use ($session): void {
            /** @var ContractWizardSession $fresh */
            $fresh = ContractWizardSession::lockForUpdate()->findOrFail($session->id);
            $fresh->update([
                'otp_verified'     => true,
                'last_activity_at' => Carbon::now(),
            ]);
            $this->persistToRedis($fresh);
        });

        $session->refresh();

        return true;
    }

    /**
     * Finalizes contract from wizard session.
     *
     * Requires otp_verified = true.
     * Creates Customer (if new) + Contract from accumulated step_data.
     * Sets session status = completed, completed_contract_id = new contract ID.
     * Clears Redis key.
     * Returns created Contract.
     *
     * @throws RuntimeException If OTP has not been verified
     */
    public function finalizeContract(ContractWizardSession $session): Contract
    {
        $this->ensureSessionIsActive($session);

        if (! $session->otp_verified) {
            throw new RuntimeException('OTP non verificato. Impossibile finalizzare il contratto.');
        }

        return DB::transaction(function () use ($session): Contract {
            /** @var ContractWizardSession $fresh */
            $fresh = ContractWizardSession::lockForUpdate()->findOrFail($session->id);

            $stepData = $fresh->step_data ?? [];

            // Resolve or create customer
            $customer = $this->resolveOrCreateCustomer($fresh, $stepData);

            // Build contract data from step_data
            $contract = $this->buildContract($customer, $fresh, $stepData);

            $fresh->update([
                'status'                 => WizardStatus::Completed->value,
                'completed_contract_id'  => $contract->id,
                'completed_at'           => Carbon::now(),
                'last_activity_at'       => Carbon::now(),
            ]);

            $this->clearRedis($fresh->uuid);

            return $contract;
        });
    }

    /**
     * Marks session as abandoned.
     *
     * Clears Redis key.
     */
    public function abandon(ContractWizardSession $session): void
    {
        DB::transaction(function () use ($session): void {
            /** @var ContractWizardSession $fresh */
            $fresh = ContractWizardSession::lockForUpdate()->findOrFail($session->id);

            $fresh->update([
                'status'           => WizardStatus::Abandoned->value,
                'last_activity_at' => Carbon::now(),
            ]);
        });

        $this->clearRedis($session->uuid);
    }

    /**
     * Restores session from Redis if available, otherwise from DB.
     *
     * Used for offline PWA reconnection.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If session not found
     */
    public function restore(string $sessionUuid): ContractWizardSession
    {
        $redisKey = $this->redisKey($sessionUuid);
        $cached   = Redis::get($redisKey);

        if ($cached !== null) {
            $data = json_decode($cached, true);
            if (is_array($data)) {
                /** @var ContractWizardSession|null $session */
                $session = ContractWizardSession::where('uuid', $sessionUuid)->first();
                if ($session !== null) {
                    // Re-hydrate from Redis data for any fields that may have
                    // been updated since last DB sync
                    $session->fill([
                        'step_data'        => $data['step_data'] ?? $session->step_data,
                        'current_step'     => $data['current_step'] ?? $session->current_step,
                        'last_activity_at' => isset($data['last_activity_at'])
                            ? Carbon::parse($data['last_activity_at'])
                            : $session->last_activity_at,
                    ]);
                    return $session;
                }
            }
        }

        // Fall back to DB
        /** @var ContractWizardSession $session */
        $session = ContractWizardSession::where('uuid', $sessionUuid)->firstOrFail();

        return $session;
    }

    // ---- Private helpers ----

    private function ensureSessionIsActive(ContractWizardSession $session): void
    {
        if ($session->status !== WizardStatus::InProgress) {
            throw new RuntimeException(
                "La sessione wizard è già in stato '{$session->status->label()}'."
            );
        }

        if ($session->isExpired()) {
            throw new RuntimeException('La sessione wizard è scaduta.');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateStepData(WizardStep $step, array $data): void
    {
        $required = $step->requiredFields();
        $missing  = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw new RuntimeException(
                "Campi obbligatori mancanti per lo step '{$step->label()}': " . implode(', ', $missing)
            );
        }
    }

    private function generateOtpCode(int $length): string
    {
        $max  = (int) str_pad('', $length, '9');
        $code = random_int(0, $max);
        return str_pad((string) $code, $length, '0', STR_PAD_LEFT);
    }

    private function dispatchSms(string $phone, string $otpCode): void
    {
        // In production this would integrate with an SMS gateway.
        // Example: app(SmsGateway::class)->send($phone, "Il tuo codice OTP è: {$otpCode}");
        // For now: no-op when not in mock mode but gateway not configured.
        \Illuminate\Support\Facades\Log::info('OTP SMS dispatch', [
            'phone'   => $phone,
            'message' => "Il tuo codice di firma è: {$otpCode}",
        ]);
    }

    private function persistToRedis(ContractWizardSession $session): void
    {
        $ttlSeconds = (int) config('wizard-mobile.session_ttl_hours', 24) * 3600;
        $key        = $this->redisKey($session->uuid);
        $payload    = json_encode([
            'id'               => $session->id,
            'uuid'             => $session->uuid,
            'current_step'     => $session->current_step,
            'step_data'        => $session->step_data,
            'status'           => $session->status->value,
            'otp_verified'     => $session->otp_verified,
            'last_activity_at' => $session->last_activity_at?->toIso8601String(),
            'expires_at'       => $session->expires_at?->toIso8601String(),
        ]);

        Redis::setex($key, $ttlSeconds, (string) $payload);
    }

    private function clearRedis(string $uuid): void
    {
        Redis::del($this->redisKey($uuid));
    }

    private function redisKey(string $uuid): string
    {
        return config('wizard-mobile.redis_prefix', 'wizard:session:') . $uuid;
    }

    /**
     * @param array<string, mixed> $stepData
     */
    private function resolveOrCreateCustomer(ContractWizardSession $session, array $stepData): Customer
    {
        if ($session->customer_id !== null) {
            /** @var Customer $customer */
            $customer = Customer::findOrFail($session->customer_id);
            return $customer;
        }

        $clienteData = $stepData['Cliente'] ?? [];

        /** @var Customer $customer */
        $customer = Customer::create([
            'tenant_id'              => null, // Will be set by tenant context if applicable
            'type'                   => 'privato',
            'nome'                   => $clienteData['nome'] ?? '',
            'cognome'                => $clienteData['cognome'] ?? '',
            'codice_fiscale'         => $clienteData['codice_fiscale'] ?? '',
            'piva'                   => $clienteData['piva'] ?? null,
            'email'                  => $clienteData['email'] ?? '',
            'telefono'               => $clienteData['telefono'] ?? '',
            'cellulare'              => $clienteData['cellulare'] ?? null,
            'indirizzo_fatturazione' => $stepData['Indirizzo'] ?? [],
            'payment_method'         => $stepData['Pagamento']['payment_method'] ?? 'bonifico',
            'iban'                   => $stepData['Pagamento']['iban'] ?? null,
            'status'                 => 'active',
        ]);

        return $customer;
    }

    /**
     * @param array<string, mixed> $stepData
     */
    private function buildContract(Customer $customer, ContractWizardSession $session, array $stepData): Contract
    {
        $offertaData   = $stepData['Offerta'] ?? [];
        $indirizzoData = $stepData['Indirizzo'] ?? [];
        $pagamentoData = $stepData['Pagamento'] ?? [];

        /** @var Contract $contract */
        $contract = Contract::create([
            'customer_id'              => $customer->id,
            'service_plan_id'          => $offertaData['service_plan_id'] ?? null,
            'indirizzo_installazione'  => $indirizzoData,
            'billing_cycle'            => $offertaData['billing_cycle'] ?? 'monthly',
            'billing_day'              => $offertaData['billing_day'] ?? 1,
            'monthly_price'            => $offertaData['monthly_price'] ?? 0,
            'activation_fee'           => $offertaData['activation_fee'] ?? 0,
            'status'                   => 'pending_signature',
            'agent_id'                 => $session->agent_id,
            'signed_at'                => Carbon::now(),
        ]);

        return $contract;
    }
}
