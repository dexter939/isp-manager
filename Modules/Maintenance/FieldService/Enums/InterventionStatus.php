<?php
namespace Modules\Maintenance\FieldService\Enums;
enum InterventionStatus: string {
    case Scheduled  = 'scheduled';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
}
