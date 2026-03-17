<?php

namespace Modules\Contracts\WizardMobile\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Contracts\WizardMobile\Http\Requests\VerifyOtpRequest;
use Modules\Contracts\WizardMobile\Models\ContractWizardSession;
use Modules\Contracts\WizardMobile\Services\WizardSessionService;

class WizardMobileController extends ApiController
{
    public function __construct(private readonly WizardSessionService $service) {}

    public function create(Request $request): JsonResponse
    {
        $session = $this->service->create(
            agentId:    $request->user()->id,
            customerId: $request->input('customer_id')
        );

        return $this->created(['data' => $session]);
    }

    public function show(string $uuid): JsonResponse
    {
        $session = $this->service->restore($uuid);
        return $this->success(['data' => $session]);
    }

    public function saveStep(Request $request, string $uuid, int $step): JsonResponse
    {
        $session = ContractWizardSession::where('uuid', $uuid)->firstOrFail();
        $session = $this->service->saveStep($session, $step, $request->all());

        return $this->success(['data' => $session]);
    }

    public function sendOtp(string $uuid): JsonResponse
    {
        $session = ContractWizardSession::where('uuid', $uuid)->firstOrFail();
        $this->service->sendOtp($session);

        return $this->success(['message' => 'OTP inviato.']);
    }

    public function verifyOtp(VerifyOtpRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();

        $session  = ContractWizardSession::where('uuid', $uuid)->firstOrFail();
        $verified = $this->service->verifyOtp($session, $validated['otp']);

        if (!$verified) {
            return $this->error('OTP non valido o scaduto.', 422);
        }

        return $this->success(['message' => 'OTP verificato.']);
    }

    public function finalize(string $uuid): JsonResponse
    {
        $session  = ContractWizardSession::where('uuid', $uuid)->firstOrFail();
        $contract = $this->service->finalizeContract($session);

        return $this->success([
            'data'    => ['contract_id' => $contract->id],
            'message' => 'Contratto creato con successo.',
        ]);
    }

    public function abandon(string $uuid): JsonResponse
    {
        $session = ContractWizardSession::where('uuid', $uuid)->firstOrFail();
        $this->service->abandon($session);

        return $this->success(['message' => 'Sessione wizard abbandonata.']);
    }
}
