<?php
namespace Modules\Maintenance\FieldService\Events;
use Modules\Maintenance\FieldService\Models\FieldIntervention;
class InterventionScheduled { public function __construct(public readonly FieldIntervention $intervention) {} }
