<?php

declare(strict_types=1);

namespace Modules\Maintenance\Dispatcher\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Maintenance\Dispatcher\Models\DispatchAssignment;

class DispatchAssignmentFactory extends Factory
{
    protected $model = DispatchAssignment::class;

    public function definition(): array
    {
        $start    = Carbon::parse($this->faker->dateTimeBetween('now', '+30 days'))->setMinutes(0)->setSeconds(0);
        $duration = $this->faker->randomElement([60, 90, 120, 180]);

        return [
            'tenant_id'                  => 1,
            'intervention_id'            => Str::uuid()->toString(),
            'technician_id'              => Str::uuid()->toString(),
            'scheduled_start'            => $start,
            'scheduled_end'              => $start->copy()->addMinutes($duration),
            'estimated_duration_minutes' => $duration,
            'travel_time_minutes'        => 0,
            'status'                     => 'scheduled',
            'notes'                      => null,
        ];
    }

    public function forDate(string $date): static
    {
        $start = Carbon::parse($date . ' 09:00:00');
        return $this->state([
            'scheduled_start' => $start,
            'scheduled_end'   => $start->copy()->addMinutes(120),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
