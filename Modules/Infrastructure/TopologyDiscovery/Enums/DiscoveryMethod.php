<?php
namespace Modules\Infrastructure\TopologyDiscovery\Enums;
enum DiscoveryMethod: string {
    case Lldp       = 'lldp';
    case Cdp        = 'cdp';
    case SnmpArp    = 'snmp_arp';
    case SnmpBridge = 'snmp_bridge';
}
