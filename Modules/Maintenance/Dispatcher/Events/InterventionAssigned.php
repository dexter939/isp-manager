<?php
namespace Modules\Maintenance\Dispatcher\Events;
use Illuminate\Foundation\Events\Dispatchable;
class InterventionAssigned {
    use Dispatchable;
    public function __construct(
        public readonly string $interventionId,
        public readonly string $technicianId,
        public readonly \Carbon\Carbon $scheduledStart,
    ) {}
}
