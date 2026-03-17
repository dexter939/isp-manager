<?php
namespace Modules\Network\FairUsage\Enums;
enum FupStatus: string {
    case Normal    = 'normal';
    case Warning   = 'warning';
    case Throttled = 'throttled';
    case Exhausted = 'exhausted';
}
