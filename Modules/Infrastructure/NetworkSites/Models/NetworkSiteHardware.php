<?php
namespace Modules\Infrastructure\NetworkSites\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class NetworkSiteHardware extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'network_site_hardware';
    protected $guarded = ['id'];
    protected $casts = ['is_access_device'=>'boolean','linked_at'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function site() { return $this->belongsTo(NetworkSite::class, 'network_site_id'); }
}
