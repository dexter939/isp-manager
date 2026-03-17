<?php
namespace Modules\Maintenance\FieldService\Events;
class TechnicianPositionUpdated { public function __construct(public readonly int $technicianId, public readonly float $lat, public readonly float $lon) {} }
