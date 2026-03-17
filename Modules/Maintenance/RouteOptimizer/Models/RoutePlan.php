<?php
namespace Modules\Maintenance\RouteOptimizer\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class RoutePlan extends Model {
    use HasUuids;
    protected $table = 'route_plans';
    protected $guarded = ['id'];
    protected $casts = ['plan_date'=>'date','optimized_order'=>'array','start_lat'=>'decimal:8','start_lon'=>'decimal:8','total_distance_km'=>'decimal:3'];
    public function uniqueIds(): array { return ['id']; }
}
