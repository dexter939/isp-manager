<?php
namespace Modules\Infrastructure\NetworkSites\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class NetworkSiteCustomerService extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'network_site_customer_services';
    protected $guarded = ['id'];
    public function uniqueIds(): array { return ['id']; }
}
