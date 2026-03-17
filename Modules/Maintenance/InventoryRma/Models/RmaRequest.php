<?php
namespace Modules\Maintenance\InventoryRma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class RmaRequest extends Model {
    use HasUuids;
    protected $table = 'rma_requests';
    protected $guarded = ['id'];
    protected $casts = ['shipped_at'=>'datetime','resolved_at'=>'datetime'];
    public function uniqueIds(): array { return ['id']; }
    public function isOpen(): bool { return $this->resolved_at === null; }
}
