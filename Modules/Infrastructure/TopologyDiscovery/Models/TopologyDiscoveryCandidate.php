<?php
namespace Modules\Infrastructure\TopologyDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Infrastructure\TopologyDiscovery\Enums\DiscoveryStatus;
use Modules\Infrastructure\TopologyDiscovery\Enums\DiscoveryMethod;
class TopologyDiscoveryCandidate extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'topology_discovery_candidates';
    protected $guarded = ['id'];
    protected $casts = ['status'=>DiscoveryStatus::class,'discovery_method'=>DiscoveryMethod::class,'created_at'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function run() { return $this->belongsTo(TopologyDiscoveryRun::class, 'discovery_run_id'); }
}
