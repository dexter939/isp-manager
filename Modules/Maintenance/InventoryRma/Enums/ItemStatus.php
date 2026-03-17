<?php
namespace Modules\Maintenance\InventoryRma\Enums;
enum ItemStatus: string {
    case InStock         = 'in_stock';
    case AssignedVehicle = 'assigned_vehicle';
    case Deployed        = 'deployed';
    case RmaPending      = 'rma_pending';
    case RmaInTransit    = 'rma_in_transit';
    case RmaApproved     = 'rma_approved';
    case Replaced        = 'replaced';
    case Decommissioned  = 'decommissioned';
    public function isRma(): bool { return in_array($this, [self::RmaPending, self::RmaInTransit, self::RmaApproved]); }
}
