<?php
namespace Modules\Contracts\AgentNetwork\Events;
use Modules\Contracts\AgentNetwork\Models\CommissionEntry;
class CommissionCalculated {
    public function __construct(public readonly CommissionEntry $entry) {}
}
