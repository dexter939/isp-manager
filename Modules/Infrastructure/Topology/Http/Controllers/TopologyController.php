<?php
namespace Modules\Infrastructure\Topology\Http\Controllers;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use Modules\Infrastructure\Topology\Http\Requests\StoreLinkRequest;
use Modules\Infrastructure\Topology\Http\Requests\UpdateLinkRequest;
use Modules\Infrastructure\Topology\Models\TopologyLink;
use Modules\Infrastructure\Topology\Services\TopologyService;
use Modules\Infrastructure\Topology\Http\Resources\TopologyLinkResource;
class TopologyController extends ApiController {
    public function __construct(private TopologyService $service) {}
    public function graphForSite(string $siteId): JsonResponse { return $this->success($this->service->getGraph($siteId)); }
    public function fullGraph(): JsonResponse { return $this->success($this->service->getGraph()); }
    public function storeLink(StoreLinkRequest $request): JsonResponse {
        $data = $request->validated();
        $link = TopologyLink::create($data);
        return $this->created(new TopologyLinkResource($link));
    }
    public function updateLink(UpdateLinkRequest $request, TopologyLink $link): JsonResponse {
        $link->update($request->validated());
        return $this->success(new TopologyLinkResource($link));
    }
    public function destroyLink(TopologyLink $link): JsonResponse { $link->delete(); return $this->noContent(); }
    public function deviceImpact(string $deviceId): JsonResponse {
        $impacted = $this->service->getImpactedDevices($deviceId);
        return $this->success(['failed_device_id'=>$deviceId,'impacted_device_ids'=>$impacted->values(),'impacted_count'=>$impacted->count()]);
    }
}
