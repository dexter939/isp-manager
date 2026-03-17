<?php
namespace Modules\Infrastructure\NetworkSites\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class NetworkSiteDocument extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'network_site_documents';
    protected $guarded = ['id'];
    protected $casts = ['created_at'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
}
