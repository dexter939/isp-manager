<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Contracts\Http\Requests\VerifyOtpRequest;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Services\FEAService;

/**
 * Endpoint pubblico per la verifica OTP e firma FEA.
 * Non richiede autenticazione Sanctum (il cliente firma dal link ricevuto via SMS).
 */
class SignatureController extends Controller
{
    public function __construct(
        private readonly FEAService $feaService,
    ) {}

    /**
     * Verifica OTP e completa la firma FEA.
     * Il contratto viene attivato automaticamente se l'OTP è corretto.
     */
    public function verify(VerifyOtpRequest $request, Contract $contract): JsonResponse
    {
        // Solo contratti in pending_signature possono essere firmati
        if (!$contract->status->canBeSigned()) {
            return response()->json([
                'message' => 'Il contratto non è in attesa di firma.',
            ], 409);
        }

        try {
            $result = $this->feaService->verifyAndSign(
                contract: $contract,
                otp: $request->input('otp'),
                clientIp: $request->ip(),
                userAgent: $request->userAgent() ?? '',
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'message'       => 'Contratto firmato con successo.',
            'signed_at'     => $result->signedAt->toIso8601String(),
            'pdf_hash'      => $result->pdfHashSha256,
            'contract_id'   => $result->contractId,
        ]);
    }

    /** Pagina pubblica per inserimento OTP (per clienti senza portale) */
    public function showSignaturePage(Contract $contract): \Illuminate\View\View
    {
        abort_unless($contract->status->canBeSigned(), 410, 'Questo link di firma non è più valido.');

        $contract->load(['customer', 'servicePlan']);

        return view('contracts::signature', compact('contract'));
    }
}
