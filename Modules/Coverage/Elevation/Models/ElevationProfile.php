<?php
namespace Modules\Coverage\Elevation\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Coverage\Elevation\Database\Factories\ElevationProfileFactory;
class ElevationProfile extends Model {
    use HasUuids, HasFactory;
    protected static function newFactory(): ElevationProfileFactory { return ElevationProfileFactory::new(); }
    public $timestamps = false;
    protected $table = 'elevation_profiles';
    protected $guarded = ['id'];
    protected $casts = ['profile_data'=>'array','has_obstruction'=>'boolean','calculated_at'=>'datetime','created_at'=>'datetime','customer_lat'=>'decimal:8','customer_lon'=>'decimal:8','distance_km'=>'decimal:3','frequency_ghz'=>'decimal:2'];
    public function uniqueIds(): array { return ['id']; }
}
