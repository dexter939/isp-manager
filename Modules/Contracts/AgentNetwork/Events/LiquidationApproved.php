<?php
namespace Modules\Contracts\AgentNetwork\Events;
use Modules\Contracts\AgentNetwork\Models\CommissionLiquidation;
class LiquidationApproved {
    public function __construct(public readonly CommissionLiquidation $liquidation) {}
}
