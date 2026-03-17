<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum FloatingIpStatus: string
{
    case MasterActive   = 'master_active';
    case FailoverActive = 'failover_active';
    case BothDown       = 'both_down';

    public function label(): string
    {
        return match($this) {
            self::MasterActive   => 'Master Active',
            self::FailoverActive => 'Failover Active',
            self::BothDown       => 'Both Down',
        };
    }
}
