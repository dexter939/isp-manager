<?php

namespace Modules\Billing\PosteItaliane\Enums;

enum BollettinoStatus: string
{
    case Generated = 'generated';
    case Printed   = 'printed';
    case Paid      = 'paid';
    case Expired   = 'expired';
}
