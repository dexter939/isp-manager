<?php
namespace Modules\Infrastructure\TopologyDiscovery\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Infrastructure\TopologyDiscovery\Http\Requests\BulkConfirmRequest;
use Modules\Infrastructure\TopologyDiscovery\Models\TopologyDiscoveryRun;
use Modules\Infrastructure\TopologyDiscovery\Models\TopologyDiscoveryCandidate;
use Modules\Infrastructure\TopologyDiscovery\Services\TopologyDiscoveryService;
class DiscoveryController extends ApiController {
    public function __construct(private TopologyDiscoveryService $service) {}
    public function run(): JsonResponse {
        $run = $this->service->runDiscovery();
        return response()->json($run, 201);
    }
    public function runs(): JsonResponse {
        return response()->json(TopologyDiscoveryRun::orderByDesc('started_at')->paginate(20));
    }
    public function candidates(Request $request): JsonResponse {
        $candidates = TopologyDiscoveryCandidate::when($request->input('status'), fn($q,$s) => $q->where('status',$s))->when($request->input('run_id'), fn($q,$r) => $q->where('discovery_run_id',$r))->orderByDesc('created_at')->paginate(50);
        return response()->json($candidates);
    }
    public function confirm(TopologyDiscoveryCandidate $candidate): JsonResponse {
        $link = $this->service->confirmCandidate($candidate);
        return response()->json($link);
    }
    public function reject(TopologyDiscoveryCandidate $candidate): JsonResponse {
        $this->service->rejectCandidate($candidate);
        return response()->json(['message'=>'Candidate rejected']);
    }
    public function bulkConfirm(BulkConfirmRequest $request): JsonResponse {
        $data    = $request->validated();
        $count   = $this->service->bulkConfirm($data['candidate_ids']);
        return response()->json(['confirmed'=>$count]);
    }
}
