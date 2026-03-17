<?php
namespace Modules\Infrastructure\NetworkSites\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Infrastructure\NetworkSites\Enums\SiteType;
use Modules\Infrastructure\NetworkSites\Enums\SiteStatus;
class NetworkSite extends Model {
    use HasUuids;
    protected $table = 'network_sites';
    protected $guarded = ['id'];
    protected $casts = ['type'=>SiteType::class,'status'=>SiteStatus::class,'lease_expiry'=>'date','latitude'=>'decimal:8','longitude'=>'decimal:8'];
    public function uniqueIds(): array { return ['id']; }
    public function hardware() { return $this->hasMany(NetworkSiteHardware::class, 'network_site_id'); }
    public function documents() { return $this->hasMany(NetworkSiteDocument::class, 'network_site_id'); }
    public function customerServices() { return $this->hasMany(NetworkSiteCustomerService::class, 'network_site_id'); }
}
