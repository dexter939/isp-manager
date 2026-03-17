<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum DnsFilterAction: string
{
    case Allowed = 'allowed';
    case Blocked = 'blocked';
}
