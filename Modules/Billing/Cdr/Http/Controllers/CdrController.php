<?php
namespace Modules\Billing\Cdr\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Billing\Cdr\Jobs\CdrImportJob;
use Modules\Billing\Cdr\Models\AnagrafeTributariaExport;
use Modules\Billing\Cdr\Models\CdrImportFile;
use Modules\Billing\Cdr\Models\CdrRecord;
use Modules\Billing\Cdr\Services\AnagrafeTributariaExporter;
use Modules\Billing\Cdr\Services\CdrImporter;
use Modules\Billing\Cdr\Http\Requests\ImportCdrFileRequest;
use Modules\Billing\Cdr\Http\Requests\ExportAnagrafeRequest;

class CdrController extends ApiController
{
    public function __construct(
        private readonly CdrImporter $importer,
        private readonly AnagrafeTributariaExporter $exporter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $records = CdrRecord::query()
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->billed !== null, fn($q) => $q->where('billed', (bool) $request->billed))
            ->when($request->from, fn($q) => $q->where('start_time', '>=', $request->from))
            ->when($request->to, fn($q) => $q->where('start_time', '<=', $request->to))
            ->latest('start_time')
            ->paginate(50);

        return response()->json(['data' => $records]);
    }

    public function importFile(ImportCdrFileRequest $request): JsonResponse
    {
        $validated  = $request->validated();

        $file       = $request->file('file');
        $filename   = $file->getClientOriginalName();
        $format     = $validated['format'] ?? 'auto';

        $importFile = CdrImportFile::create([
            'filename' => $filename,
            'format'   => $format === 'auto' ? 'generic' : $format,
            'status'   => 'pending',
        ]);

        // Store file to MinIO
        $file->storeAs('cdr-imports', $filename, 'minio');

        // Queue import job
        CdrImportJob::dispatch($importFile->id);

        return response()->json(['data' => $importFile, 'message' => 'Import avviato.'], 201);
    }

    public function importStatus(int $id): JsonResponse
    {
        return response()->json(['data' => CdrImportFile::findOrFail($id)]);
    }

    public function tariffPlans(): JsonResponse
    {
        return response()->json(['data' => \Modules\Billing\Cdr\Models\CdrTariffPlan::with('rates')->get()]);
    }

    public function exportAnagrafe(ExportAnagrafeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $export = $this->exporter->export((int) $validated['year']);
        return response()->json(['data' => $export, 'message' => 'Export Anagrafe Tributaria generato.']);
    }
}
