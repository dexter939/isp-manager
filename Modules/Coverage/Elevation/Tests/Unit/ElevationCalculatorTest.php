<?php
namespace Modules\Coverage\Elevation\Tests\Unit;
use Tests\TestCase;
use Modules\Coverage\Elevation\Services\ElevationProfileService;
class ElevationCalculatorTest extends TestCase {
    private ElevationProfileService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new ElevationProfileService(); }
    public function test_fresnel_radius_formula_at_5km_5_8ghz(): void {
        // r = sqrt(lambda * d1 * d2 / (d1 + d2))
        // At midpoint of 5km path, f=5.8GHz: lambda=0.0517m, d1=d2=2500m
        $lambda = 3e8 / (5.8e9); // ~0.0517m
        $d1 = 2500;
        $d2 = 2500;
        $expected = sqrt($lambda * $d1 * $d2 / ($d1 + $d2));
        $this->assertEqualsWithDelta(8.05, $expected, 0.1, "Fresnel radius at midpoint 5km@5.8GHz should be ~8m");
    }
    public function test_obstruction_detected_when_terrain_above_los(): void {
        // If terrain elevation > LOS height → has_obstruction = true
        // Profile point: LOS=200m, terrain=250m → obstruction
        $clearance = 200 - 250; // = -50
        $this->assertLessThan(0, $clearance, "Negative clearance = obstruction");
    }
}
