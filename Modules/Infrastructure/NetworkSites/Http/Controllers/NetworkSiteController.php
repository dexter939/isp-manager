<?php
namespace Modules\Infrastructure\NetworkSites\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Modules\Infrastructure\NetworkSites\Http\Requests\StoreNetworkSiteRequest;
use Modules\Infrastructure\NetworkSites\Http\Requests\UpdateNetworkSiteRequest;
use Modules\Infrastructure\NetworkSites\Http\Requests\LinkHardwareRequest;
use Modules\Infrastructure\NetworkSites\Http\Requests\BulkLinkCustomerServicesRequest;
use Modules\Infrastructure\NetworkSites\Models\NetworkSite;
use Modules\Infrastructure\NetworkSites\Services\NetworkSiteService;
use Modules\Infrastructure\NetworkSites\Http\Resources\NetworkSiteResource;
class NetworkSiteController extends ApiController {
    public function __construct(private NetworkSiteService $service) {}
    public function index(Request $request): JsonResponse {
        $sites = NetworkSite::query()->when($request->input('type'), fn($q,$t) => $q->where('type',$t))->when($request->input('status'), fn($q,$s) => $q->where('status',$s))->paginate(25);
        return response()->json(NetworkSiteResource::collection($sites));
    }
    public function store(StoreNetworkSiteRequest $request): JsonResponse {
        $data = $request->validated();
        $site = NetworkSite::create($data);
        return response()->json(new NetworkSiteResource($site), 201);
    }
    public function show(NetworkSite $networkSite): JsonResponse { return response()->json(new NetworkSiteResource($networkSite)); }
    public function update(UpdateNetworkSiteRequest $request, NetworkSite $networkSite): JsonResponse {
        $networkSite->update($request->validated());
        return response()->json(new NetworkSiteResource($networkSite));
    }
    public function destroy(NetworkSite $networkSite): JsonResponse { $networkSite->delete(); return response()->json(null, 204); }
    public function stats(NetworkSite $networkSite): JsonResponse { return response()->json($this->service->getWithStats($networkSite)); }
    public function hardware(NetworkSite $networkSite): JsonResponse { return response()->json($networkSite->hardware()->get()); }
    public function linkHardware(LinkHardwareRequest $request, NetworkSite $networkSite): JsonResponse {
        $data = $request->validated();
        $this->service->linkHardware($networkSite, $data['hardware_id'], $data['is_access_device'] ?? false);
        return response()->json(['message'=>'Hardware linked']);
    }
    public function customerServices(NetworkSite $networkSite): JsonResponse { return response()->json($networkSite->customerServices()->get()); }
    public function bulkLinkCustomerServices(BulkLinkCustomerServicesRequest $request, NetworkSite $networkSite): JsonResponse {
        $data = $request->validated();
        $count = $this->service->bulkLinkCustomerServices($networkSite, $data['hardware_id'], $data['contract_ids']);
        return response()->json(['linked'=>$count]);
    }
    public function map(): JsonResponse {
        $sites = NetworkSite::whereNotNull('latitude')->whereNotNull('longitude')->where('status','active')->get(['id','name','type','latitude','longitude','status','importance']);
        return response()->json($sites);
    }
}
