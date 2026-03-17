<?php
namespace Modules\Maintenance\InventoryRma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class InventoryModel extends Model {
    use HasUuids;
    protected $table = 'inventory_models';
    protected $guarded = ['id'];
    public function uniqueIds(): array { return ['id']; }
}
