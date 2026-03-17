<?php
namespace Modules\Infrastructure\Topology\Enums;
enum LinkType: string {
    case Fiber     = 'fiber';
    case Radio     = 'radio';
    case Copper    = 'copper';
    case Uplink    = 'uplink';
    case Aggregate = 'aggregate';
    case Other     = 'other';
}
