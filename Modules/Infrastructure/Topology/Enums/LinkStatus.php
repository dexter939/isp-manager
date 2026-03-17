<?php
namespace Modules\Infrastructure\Topology\Enums;
enum LinkStatus: string {
    case Up       = 'up';
    case Down     = 'down';
    case Degraded = 'degraded';
    case Unknown  = 'unknown';
}
