<?php

declare(strict_types=1);

namespace Modules\Contracts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\ApiController;
use Modules\Contracts\Http\Requests\StoreContractRequest;
use Modules\Contracts\Http\Resources\ContractResource;
use Modules\Contracts\Models\Contract;
use Modules\Contracts\Models\Customer;
use Modules\Contracts\Models\ServicePlan;
use Modules\Contracts\Services\ContractService;
use Modules\Contracts\Services\DocumentStorageService;
use Modules\Contracts\Services\FEAService;
use Modules\Contracts\Services\PdfGeneratorService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ContractController extends ApiController
{
    public function __construct(
        private readonly ContractService       $contractService,
        private readonly FEAService            $feaService,
        private readonly PdfGeneratorService   $pdfGenerator,
        private readonly DocumentStorageService $storage,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $contracts = QueryBuilder::for(
            Contract::where('tenant_id', $request->user()->tenant_id)
                    ->with(['customer', 'servicePlan'])
        )
        ->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::exact('carrier'),
            AllowedFilter::exact('customer_id'),
            AllowedFilter::exact('billing_day'),
        ])
        ->allowedSorts(['created_at', 'signed_at', 'activation_date', 'monthly_price'])
        ->defaultSort('-created_at')
        ->paginate($request->integer('per_page', 20));

        return response()->json(ContractResource::collection($contracts));
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        $this->authorize('create', Contract::class);

        $customer = Customer::findOrFail($request->customer_id);
        $plan     = ServicePlan::findOrFail($request->service_plan_id);

        $data             = $request->validated();
        $data['agent_id'] = $request->user()->id;

        $contract = $this->contractService->create($customer, $plan, $data);

        return response()->json(new ContractResource($contract->load(['customer', 'servicePlan'])), 201);
    }

    public function show(Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        $contract->load(['customer', 'servicePlan', 'agent', 'latestSignature', 'documents']);

        return response()->json(new ContractResource($contract));
    }

    /** Genera PDF anteprima (scaricabile prima della firma) */
    public function preview(Contract $contract): Response
    {
        $this->authorize('view', $contract);

        $pdfContent = $this->pdfGenerator->preview($contract);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"contratto_{$contract->id}_anteprima.pdf\"",
        ]);
    }

    /**
     * Invia il contratto per la firma FEA.
     * Genera il PDF definitivo e invia OTP al cliente.
     */
    public function sendForSignature(Contract $contract, Request $request): JsonResponse
    {
        $this->authorize('update', $contract);

        $channel = $request->input('channel', 'sms');

        // 1. Genera PDF e porta contratto in pending_signature
        $this->contractService->sendForSignature($contract, $this->pdfGenerator);

        // 2. Invia OTP
        $signature = $this->feaService->sendOtp($contract->fresh(), $channel);

        return response()->json([
            'message'      => 'OTP inviato al cliente.',
            'otp_sent_to'  => $signature->otp_sent_to,
            'otp_expires'  => $signature->otp_expires_at,
        ]);
    }

    /** Ri-invia OTP (se il cliente non l'ha ricevuto) */
    public function resendOtp(Contract $contract, Request $request): JsonResponse
    {
        $this->authorize('update', $contract);

        $channel   = $request->input('channel', 'sms');
        $signature = $this->feaService->sendOtp($contract, $channel);

        return response()->json([
            'message'      => 'Nuovo OTP inviato.',
            'otp_sent_to'  => $signature->otp_sent_to,
            'otp_expires'  => $signature->otp_expires_at,
        ]);
    }

    /** Download del PDF contratto firmato (URL temporaneo MinIO) */
    public function downloadPdf(Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        if (!$contract->pdf_path) {
            return response()->json(['message' => 'PDF non ancora disponibile.'], 404);
        }

        $url = $this->storage->temporaryDownloadUrl($contract->pdf_path, 5);

        return response()->json(['url' => $url, 'expires_in' => 300]);
    }

    /** Cessa il contratto */
    public function terminate(Contract $contract, Request $request): JsonResponse
    {
        $this->authorize('update', $contract);

        $reason = $request->input('reason');

        try {
            $this->contractService->terminate($contract, $reason);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contratto cessato.']);
    }
}
