<?php
namespace Modules\Infrastructure\TopologyDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class TopologyDiscoveryRun extends Model {
    use HasUuids;
    public $timestamps = false;
    protected $table = 'topology_discovery_runs';
    protected $guarded = ['id'];
    protected $casts = ['started_at'=>'datetime','completed_at'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function candidates() { return $this->hasMany(TopologyDiscoveryCandidate::class, 'discovery_run_id'); }
}
