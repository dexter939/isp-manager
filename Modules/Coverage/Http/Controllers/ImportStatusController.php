<?php

declare(strict_types=1);

namespace Modules\Coverage\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Coverage\Http\Requests\TriggerImportRequest;
use Modules\Coverage\Jobs\ImportCoverageJob;

class ImportStatusController extends ApiController
{
    /**
     * GET /api/v1/coverage/import-status
     * Stato degli ultimi import per ogni carrier.
     */
    public function index(): JsonResponse
    {
        $statuses = DB::table('coverage_import_logs')
            ->select(['carrier', 'status', 'source_file', 'rows_processed',
                'rows_inserted', 'rows_updated', 'rows_failed',
                'started_at', 'completed_at', 'duration_seconds', 'error_message'])
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('coverage_import_logs')
                    ->groupBy('carrier');
            })
            ->get();

        $counts = [
            'fibercop'  => DB::table('coverage_fibercop')->count(),
            'openfiber' => DB::table('coverage_openfiber')->count(),
            'registry'  => DB::table('address_registry')->count(),
        ];

        return $this->success([
            'data'   => $statuses,
            'counts' => $counts,
        ]);
    }

    /**
     * POST /api/v1/coverage/import
     * Triggera manualmente un import (solo admin).
     */
    public function trigger(TriggerImportRequest $request): JsonResponse
    {
        $this->authorize('coverage.import');

        $data     = $request->validated();
        $carrier  = $data['carrier'];
        $filePath = $data['file_path'];

        ImportCoverageJob::dispatch($carrier, $filePath)->onQueue('imports');

        return $this->success(['message' => "Import {$carrier} accodato."]);
    }
}
