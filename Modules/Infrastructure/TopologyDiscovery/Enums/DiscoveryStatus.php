<?php
namespace Modules\Infrastructure\TopologyDiscovery\Enums;
enum DiscoveryStatus: string {
    case Pending     = 'pending';
    case Confirmed   = 'confirmed';
    case Rejected    = 'rejected';
    case AutoCreated = 'auto_created';
}
