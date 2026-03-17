<?php
namespace Modules\Infrastructure\NetworkSites\Enums;
enum SiteType: string {
    case Pop         = 'pop';
    case Cabinet     = 'cabinet';
    case Datacenter  = 'datacenter';
    case Mast        = 'mast';
    case Building    = 'building';
    case Other       = 'other';
    public function label(): string {
        return match($this) { self::Pop=>'POP', self::Cabinet=>'Armadio', self::Datacenter=>'Datacenter', self::Mast=>'Antenna', self::Building=>'Edificio', self::Other=>'Altro' };
    }
}
