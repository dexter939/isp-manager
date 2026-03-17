<?php

namespace Modules\Maintenance\PurchaseOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'inventory_model_id', 'supplier_id',
        'min_stock_quantity', 'reorder_quantity', 'auto_order',
    ];

    protected $casts = ['auto_order' => 'boolean'];

    public function uniqueIds(): array { return ['id']; }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
