<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\Http\Requests\StoreInvoiceRequest;
use Modules\Billing\Http\Requests\UpdateInvoiceRequest;
use Modules\Billing\Http\Resources\InvoiceResource;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Services\InvoiceService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class InvoiceController extends ApiController
{
    public function __construct(
        private readonly InvoiceService $service,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Lista fatture con filtri e paginazione.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = QueryBuilder::for(Invoice::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('contract_id'),
                AllowedFilter::exact('payment_method'),
                AllowedFilter::scope('due_on'),
                AllowedFilter::scope('overdue'),
            ])
            ->allowedSorts(['issue_date', 'due_date', 'total', 'number'])
            ->defaultSort('-issue_date')
            ->with(['customer', 'contract'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->paginate($request->integer('per_page', 20));

        return response()->json(InvoiceResource::collection($invoices));
    }

    /**
     * Dettaglio fattura con items, pagamenti e dunning steps.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'contract.servicePlan', 'items', 'payments', 'dunningSteps']);

        return response()->json(['data' => new InvoiceResource($invoice)]);
    }

    /**
     * Crea fattura manuale (bozza).
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $contract = \Modules\Contracts\Models\Contract::findOrFail($request->contract_id);
        $invoice  = $this->service->generateForContract($contract);

        return response()->json(['data' => new InvoiceResource($invoice)], 201);
    }

    /**
     * Emette la fattura (Draft → Issued).
     */
    public function issue(Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $invoice = $this->service->issue($invoice);

        return response()->json(['data' => new InvoiceResource($invoice)]);
    }

    /**
     * Segna come pagata manualmente (pagamento in contanti/bonifico).
     */
    public function markPaid(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $invoice = $this->service->markPaid(
            $invoice,
            $request->payment_method,
            $request->reference,
        );

        return response()->json(['data' => new InvoiceResource($invoice)]);
    }

    /**
     * Annulla la fattura.
     */
    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $invoice = $this->service->cancel($invoice, $request->input('reason', ''));

        return response()->json(['data' => new InvoiceResource($invoice)]);
    }

    /**
     * Download PDF della fattura.
     */
    public function downloadPdf(Invoice $invoice): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $invoice);

        if (!$invoice->pdf_path) {
            abort(404, 'PDF non ancora generato');
        }

        return \Illuminate\Support\Facades\Storage::disk('minio-invoices')
            ->response($invoice->pdf_path);
    }
}
