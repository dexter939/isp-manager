<?php
namespace Modules\Maintenance\RouteOptimizer\Services;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Maintenance\RouteOptimizer\Models\RoutePlan;
class RouteOptimizerService {
    public function optimize(string $technicianId, Carbon $date): RoutePlan {
        $technician = DB::table('users')->findOrFail($technicianId);
        $assignments = DB::table('dispatch_assignments as da')
            ->join('field_interventions as fi', 'da.intervention_id', '=', 'fi.id')
            ->where('da.technician_id', $technicianId)
            ->whereDate('da.scheduled_start', $date)
            ->where('da.status', '!=', 'cancelled')
            ->select('da.intervention_id', 'fi.latitude', 'fi.longitude', 'fi.address', 'da.scheduled_start')
            ->orderBy('da.scheduled_start')
            ->get();
        $startLat = $technician->home_lat ?? 45.4654;
        $startLon = $technician->home_lon ?? 9.1866;
        $stops = $assignments->filter(fn($a) => $a->latitude && $a->longitude)->values()->toArray();
        if (empty($stops)) {
            return RoutePlan::updateOrCreate(['technician_id'=>$technicianId,'plan_date'=>$date->toDateString()],['start_lat'=>$startLat,'start_lon'=>$startLon,'total_distance_km'=>0,'total_duration_minutes'=>0,'optimized_order'=>[],'status'=>'draft']);
        }
        $maxGreedy = config('route_optimizer.max_greedy_stops', 20);
        $orderedStops = count($stops) <= $maxGreedy ? $this->nearestNeighbor($startLat, $startLon, $stops) : $stops;
        $optimizedOrder = array_column($orderedStops, 'intervention_id');
        $totalDistance  = $this->calculateTotalDistance($startLat, $startLon, $orderedStops);
        return RoutePlan::updateOrCreate(['technician_id'=>$technicianId,'plan_date'=>$date->toDateString()],['start_lat'=>$startLat,'start_lon'=>$startLon,'start_address'=>$technician->address ?? null,'total_distance_km'=>$totalDistance,'total_duration_minutes'=>(int)($totalDistance * 1.5 * 60 / 50),'optimized_order'=>$optimizedOrder,'status'=>'draft']);
    }
    public function getDirections(RoutePlan $plan): array {
        $cacheKey = "route_directions:{$plan->id}";
        return Cache::remember($cacheKey, now()->addHours(config('route_optimizer.cache_ttl_hours', 24)), function () use ($plan) {
            if (config('app.carrier_mock', false)) {
                return ['mock'=>true,'waypoints'=>$plan->optimized_order,'total_distance_km'=>$plan->total_distance_km,'total_duration_minutes'=>$plan->total_duration_minutes];
            }
            return $this->fetchOsrmDirections($plan);
        });
    }
    private function nearestNeighbor(float $startLat, float $startLon, array $stops): array {
        $remaining = $stops;
        $ordered   = [];
        $curLat    = $startLat;
        $curLon    = $startLon;
        while (!empty($remaining)) {
            $nearest   = null;
            $minDist   = PHP_FLOAT_MAX;
            $nearestIdx = 0;
            foreach ($remaining as $i => $stop) {
                $dist = $this->haversineKm($curLat, $curLon, (float)$stop->latitude, (float)$stop->longitude);
                if ($dist < $minDist) { $minDist = $dist; $nearest = $stop; $nearestIdx = $i; }
            }
            if ($nearest) {
                $ordered[] = $nearest;
                $curLat    = (float)$nearest->latitude;
                $curLon    = (float)$nearest->longitude;
                array_splice($remaining, $nearestIdx, 1);
            }
        }
        return $ordered;
    }
    private function calculateTotalDistance(float $startLat, float $startLon, array $stops): float {
        $total  = 0;
        $curLat = $startLat;
        $curLon = $startLon;
        foreach ($stops as $stop) {
            $total += $this->haversineKm($curLat, $curLon, (float)$stop->latitude, (float)$stop->longitude);
            $curLat = (float)$stop->latitude;
            $curLon = (float)$stop->longitude;
        }
        return round($total, 3);
    }
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }
    private function fetchOsrmDirections(RoutePlan $plan): array {
        try {
            $coords = "{$plan->start_lon},{$plan->start_lat}";
            foreach ($plan->optimized_order as $intId) {
                $fi = DB::table('field_interventions')->find($intId);
                if ($fi && $fi->longitude && $fi->latitude) $coords .= ";{$fi->longitude},{$fi->latitude}";
            }
            $url      = config('route_optimizer.osrm_url')."/route/v1/driving/{$coords}?overview=false&steps=false";
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                return ['waypoints'=>$plan->optimized_order,'total_distance_km'=>round(($data['routes'][0]['distance'] ?? 0) / 1000, 3),'total_duration_minutes'=>(int)(($data['routes'][0]['duration'] ?? 0) / 60)];
            }
        } catch (\Throwable $e) { Log::warning("OSRM request failed: {$e->getMessage()}"); }
        return ['waypoints'=>$plan->optimized_order,'total_distance_km'=>$plan->total_distance_km,'total_duration_minutes'=>$plan->total_duration_minutes];
    }
}
