<?php
namespace Modules\Coverage\Elevation\Http\Controllers;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Modules\Coverage\Elevation\Http\Requests\CalculateElevationRequest;
use Modules\Coverage\Elevation\Models\ElevationProfile;
use Modules\Coverage\Elevation\Services\ElevationProfileService;
class ElevationController extends ApiController {
    public function __construct(private ElevationProfileService $service) {}
    public function calculate(CalculateElevationRequest $request): JsonResponse {
        $data    = $request->validated();
        $site    = DB::table('network_sites')->where('id', $data['network_site_id'])->firstOrFail();
        $profile = $this->service->calculate($site, $data['customer_lat'], $data['customer_lon'], $data['antenna_height_m'] ?? config('elevation.default_antenna_height_m',10), $data['cpe_height_m'] ?? config('elevation.default_cpe_height_m',3), $data['frequency_ghz'] ?? null);
        return response()->json($profile, 201);
    }
    public function show(ElevationProfile $profile): JsonResponse { return response()->json($profile); }
    public function forContract(string $contractId): JsonResponse {
        $contract = DB::table('contracts')->where('id', $contractId)->firstOrFail();
        $customer = DB::table('customers')->find($contract->customer_id);
        if (!$customer?->latitude || !$customer?->longitude) return response()->json(['error'=>'Customer has no coordinates'], 422);
        $sites = DB::table('network_sites')->where('status','active')->get();
        if ($sites->isEmpty()) return response()->json(['error'=>'No active network sites'], 422);
        $site    = $sites->first();
        $profile = ElevationProfile::where('network_site_id', $site->id)->where('customer_lat', $customer->latitude)->where('customer_lon', $customer->longitude)->latest('calculated_at')->first();
        if (!$profile) return response()->json(['error'=>'No profile calculated yet'], 404);
        return response()->json($profile);
    }
}
