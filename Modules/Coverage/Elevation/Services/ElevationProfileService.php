<?php
namespace Modules\Coverage\Elevation\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Coverage\Elevation\Models\ElevationProfile;
class ElevationProfileService {
    public function calculate(object $site, float $customerLat, float $customerLon, int $antennaHeight, int $cpeHeight, ?float $frequencyGhz): ElevationProfile {
        $cacheKey = "elevation:{$site->id}:{$customerLat}:{$customerLon}:{$antennaHeight}:{$cpeHeight}:{$frequencyGhz}";
        $ttl      = now()->addDays(config('elevation.cache_ttl_days', 7));
        return Cache::remember($cacheKey, $ttl, function () use ($site, $customerLat, $customerLon, $antennaHeight, $cpeHeight, $frequencyGhz) {
            $points     = $this->samplePoints($site->latitude, $site->longitude, $customerLat, $customerLon, config('elevation.default_sample_points', 100));
            $elevations = $this->fetchElevations($points);
            $distanceKm = $this->haversineKm($site->latitude, $site->longitude, $customerLat, $customerLon);
            $siteElevation     = $elevations[0] + $antennaHeight;
            $customerElevation = end($elevations) + $cpeHeight;
            $profileData = [];
            $hasObstruction = false;
            $minFresnelClearance = null;
            $n = count($elevations);
            for ($i = 0; $i < $n; $i++) {
                $fraction = $i / max($n - 1, 1);
                $losHeight = $siteElevation + ($customerElevation - $siteElevation) * $fraction;
                $d1 = $fraction * $distanceKm * 1000;
                $d2 = (1 - $fraction) * $distanceKm * 1000;
                $fresnelRadius = null;
                if ($frequencyGhz && $d1 > 0 && $d2 > 0) {
                    $lambda = 3e8 / ($frequencyGhz * 1e9);
                    $fresnelRadius = sqrt($lambda * $d1 * $d2 / ($d1 + $d2));
                }
                $clearance = $losHeight - $elevations[$i];
                if ($fresnelRadius && $clearance < $fresnelRadius) $hasObstruction = true;
                if ($clearance < 0) $hasObstruction = true;
                $profileData[] = ['distance_m'=>round($fraction * $distanceKm * 1000),'elevation_m'=>$elevations[$i],'los_height_m'=>round($losHeight, 1),'fresnel_radius_m'=>$fresnelRadius ? round($fresnelRadius, 1) : null,'clearance_m'=>round($clearance, 1)];
            }
            return ElevationProfile::create(['network_site_id'=>$site->id,'customer_lat'=>$customerLat,'customer_lon'=>$customerLon,'distance_km'=>$distanceKm,'max_elevation_m'=>max($elevations),'min_elevation_m'=>min($elevations),'fresnel_clearance_percent'=>$minFresnelClearance,'has_obstruction'=>$hasObstruction,'profile_data'=>$profileData,'antenna_height_m'=>$antennaHeight,'cpe_height_m'=>$cpeHeight,'frequency_ghz'=>$frequencyGhz,'calculated_at'=>now()]);
        });
    }
    private function samplePoints(float $lat1, float $lon1, float $lat2, float $lon2, int $n): array {
        $points = [];
        for ($i = 0; $i < $n; $i++) {
            $f = $i / max($n - 1, 1);
            $points[] = ['latitude'=>$lat1 + ($lat2 - $lat1) * $f,'longitude'=>$lon1 + ($lon2 - $lon1) * $f];
        }
        return $points;
    }
    private function fetchElevations(array $points): array {
        if (config('app.carrier_mock', false)) {
            return array_fill(0, count($points), rand(100, 500));
        }
        try {
            $url      = config('elevation.open_elevation_url');
            $response = Http::timeout(30)->post($url, ['locations' => $points]);
            if ($response->successful()) {
                return array_column($response->json('results', []), 'elevation');
            }
        } catch (\Throwable $e) {
            Log::warning("ElevationProfileService: API failed: {$e->getMessage()}");
        }
        return array_fill(0, count($points), 0);
    }
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }
}
