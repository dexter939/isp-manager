<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum FloatingIpResourceType: string
{
    case Ipv4       = 'ipv4';
    case Ipv4Subnet = 'ipv4_subnet';
    case Ipv6Prefix = 'ipv6_prefix';
}
