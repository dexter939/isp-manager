<?php

declare(strict_types=1);

namespace Modules\Monitoring\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Contracts\Models\Contract;
use Modules\Monitoring\Http\Requests\RunLineTestRequest;
use Modules\Monitoring\Services\LineTestingService;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class LineTestController extends ApiController
{
    public function __construct(
        private readonly LineTestingService $lineTestingService,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Lista i risultati dei line test con filtri.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Monitoring\Models\LineTestResult::class);

        $results = QueryBuilder::for(\Modules\Monitoring\Models\LineTestResult::class)
            ->allowedFilters([
                AllowedFilter::exact('carrier'),
                AllowedFilter::exact('result'),
                AllowedFilter::exact('contract_id'),
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('error_code'),
            ])
            ->allowedSorts(['created_at', 'result', 'carrier'])
            ->defaultSort('-created_at')
            ->with(['contract.customer'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->paginate($request->integer('per_page', 20));

        return $this->success($results);
    }

    /**
     * Avvia un line test manuale su un contratto.
     */
    public function run(RunLineTestRequest $request, Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        $data = $request->validated();

        $result = match($data['carrier']) {
            'openfiber' => $this->lineTestingService->testOpenFiber($contract, 'operator'),
            'fibercop'  => $this->lineTestingService->testFiberCop($contract, 'operator'),
        };

        return $this->created(['data' => $result]);
    }

    /**
     * Ultimi N test per un contratto.
     */
    public function history(Request $request, Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        $results = \Modules\Monitoring\Models\LineTestResult::forContract($contract->id)
            ->latest()
            ->limit($request->integer('limit', 10))
            ->get();

        return $this->success(['data' => $results]);
    }
}
