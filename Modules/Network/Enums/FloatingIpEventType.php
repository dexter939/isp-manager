<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum FloatingIpEventType: string
{
    case FailoverTriggered  = 'failover_triggered';
    case RecoveryTriggered  = 'recovery_triggered';
    case ManualOverride     = 'manual_override';
}
