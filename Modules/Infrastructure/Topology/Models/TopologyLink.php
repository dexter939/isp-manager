<?php
namespace Modules\Infrastructure\Topology\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Infrastructure\Topology\Database\Factories\TopologyLinkFactory;
use Modules\Infrastructure\Topology\Enums\LinkType;
use Modules\Infrastructure\Topology\Enums\LinkStatus;
class TopologyLink extends Model {
    use HasUuids, HasFactory;
    protected static function newFactory(): TopologyLinkFactory { return TopologyLinkFactory::new(); }
    protected $table = 'topology_links';
    protected $guarded = ['id'];
    protected $casts = ['link_type'=>LinkType::class,'status'=>LinkStatus::class,'is_monitored'=>'boolean','last_status_change'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
}
