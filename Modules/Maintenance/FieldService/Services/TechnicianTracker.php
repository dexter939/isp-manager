<?php

namespace Modules\Maintenance\FieldService\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Maintenance\FieldService\Events\TechnicianPositionUpdated;
use Modules\Maintenance\FieldService\Models\TechnicianPosition;

class TechnicianTracker
{
    private const REDIS_PREFIX = 'tech:pos:';
    private const REDIS_TTL    = 3600;

    /**
     * Updates technician position.
     * Stores latest in Redis + persists to DB.
     */
    public function updatePosition(int $technicianId, float $lat, float $lon, ?int $accuracy = null): void
    {
        $data = ['lat' => $lat, 'lon' => $lon, 'at' => now()->toIso8601String(), 'accuracy' => $accuracy];

        Cache::put(self::REDIS_PREFIX . $technicianId, $data, self::REDIS_TTL);

        TechnicianPosition::create([
            'technician_id'  => $technicianId,
            'latitude'       => $lat,
            'longitude'      => $lon,
            'accuracy_meters'=> $accuracy,
            'recorded_at'    => now(),
        ]);

        event(new TechnicianPositionUpdated($technicianId, $lat, $lon));
    }

    /**
     * Gets technician's last known position from Redis.
     */
    public function getLatestPosition(int $technicianId): ?array
    {
        return Cache::get(self::REDIS_PREFIX . $technicianId);
    }

    /**
     * Gets all technicians' current positions from Redis.
     */
    public function getAllPositions(): array
    {
        $positions = [];
        $keys      = Cache::getRedis()->keys(self::REDIS_PREFIX . '*');

        foreach ($keys as $key) {
            $techId = str_replace(self::REDIS_PREFIX, '', $key);
            $pos    = Cache::get($key);
            if ($pos) {
                $positions[(int) $techId] = $pos;
            }
        }

        return $positions;
    }

    /**
     * Cleans up technician_positions records older than configured retention days.
     */
    public function cleanup(): int
    {
        $days = config('field_service.position_retention_days', 30);
        return TechnicianPosition::where('recorded_at', '<', now()->subDays($days))->delete();
    }
}
