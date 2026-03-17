<?php
namespace Modules\Billing\Bundles\Enums;
enum BillingPeriod: string {
    case Monthly    = 'monthly';
    case Bimonthly  = 'bimonthly';
    case Quarterly  = 'quarterly';
    case Semiannual = 'semiannual';
    case Annual     = 'annual';
    public function months(): int {
        return match($this) { self::Monthly=>1, self::Bimonthly=>2, self::Quarterly=>3, self::Semiannual=>6, self::Annual=>12 };
    }
}
