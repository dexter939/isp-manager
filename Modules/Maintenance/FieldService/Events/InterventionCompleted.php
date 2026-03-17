<?php
namespace Modules\Maintenance\FieldService\Events;
use Modules\Maintenance\FieldService\Models\FieldIntervention;
class InterventionCompleted { public function __construct(public readonly FieldIntervention $intervention) {} }
