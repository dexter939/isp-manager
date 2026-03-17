<?php
namespace Modules\Billing\Cdr\Enums;
enum CdrCategory: string {
    case Local         = 'local';
    case National      = 'national';
    case Mobile        = 'mobile';
    case International = 'international';
    case Special       = 'special';
    case Emergency     = 'emergency';
}
