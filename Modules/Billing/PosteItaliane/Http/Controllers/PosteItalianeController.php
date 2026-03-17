<?php

namespace Modules\Billing\PosteItaliane\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\Models\Invoice;
use Modules\Billing\PosteItaliane\Models\BollettinoTd896;
use Modules\Billing\PosteItaliane\Services\BollettinoPdfGenerator;
use Modules\Billing\PosteItaliane\Services\PostePrismaExporter;
use Modules\Billing\PosteItaliane\Services\PosteReconciliationImporter;
use Modules\Billing\PosteItaliane\Http\Requests\GenerateBollettinoRequest;
use Modules\Billing\PosteItaliane\Http\Requests\ReconcileRequest;

class PosteItalianeController extends ApiController
{
    public function __construct(
        private readonly BollettinoPdfGenerator $pdfGenerator,
        private readonly PostePrismaExporter $exporter,
        private readonly PosteReconciliationImporter $importer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bollettini = BollettinoTd896::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $bollettini]);
    }

    public function generate(GenerateBollettinoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $invoice    = Invoice::findOrFail($validated['invoice_id']);
        $numero     = $this->importer->generateNumeroBollettino();

        $bollettino = BollettinoTd896::create([
            'invoice_id'        => $invoice->id,
            'customer_id'       => $invoice->customer_id,
            'numero_bollettino' => $numero,
            'importo_centesimi' => $invoice->total_cents,
            'causale'           => 'Fattura n. ' . $invoice->number,
            'conto_corrente'    => config('poste_italiane.conto_corrente'),
            'generated_at'      => now(),
            'scadenza_at'       => now()->addDays(config('poste_italiane.scadenza_giorni', 30)),
        ]);

        return response()->json(['data' => $bollettino, 'message' => 'Bollettino generato.'], 201);
    }

    public function pdf(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $bollettino = BollettinoTd896::findOrFail($id);
        $content    = $this->pdfGenerator->generate($bollettino);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bollettino_' . $bollettino->numero_bollettino . '.pdf"',
        ]);
    }

    public function prismaExport(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $query = BollettinoTd896::query()
            ->where('status', 'generated')
            ->when($request->from, fn($q) => $q->whereDate('generated_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('generated_at', '<=', $request->to))
            ->get();

        $csv = $this->exporter->export($query);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="prisma_export_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    public function reconcile(ReconcileRequest $request): JsonResponse
    {
        $file           = $request->file('file');
        $content        = file_get_contents($file->getRealPath());
        $reconciliation = $this->importer->import($content, $file->getClientOriginalName());

        return response()->json([
            'data'    => $reconciliation,
            'message' => "Riconciliazione completata: {$reconciliation->records_matched} abbinati, {$reconciliation->records_unmatched} non trovati.",
        ]);
    }
}
