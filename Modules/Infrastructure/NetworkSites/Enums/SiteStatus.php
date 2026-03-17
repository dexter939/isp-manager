<?php
namespace Modules\Infrastructure\NetworkSites\Enums;
enum SiteStatus: string {
    case Active          = 'active';
    case Maintenance     = 'maintenance';
    case Decommissioned  = 'decommissioned';
}
