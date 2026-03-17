<?php
namespace Modules\Maintenance\FieldService\Enums;
enum InterventionType: string {
    case Installation = 'installation';
    case Repair       = 'repair';
    case Maintenance  = 'maintenance';
    case Inspection   = 'inspection';
    case Removal      = 'removal';
}
