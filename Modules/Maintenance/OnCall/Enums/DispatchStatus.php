<?php
namespace Modules\Maintenance\OnCall\Enums;
enum DispatchStatus: string {
    case Pending      = 'pending';
    case Acknowledged = 'acknowledged';
    case Escalated    = 'escalated';
    case Expired      = 'expired';
}
