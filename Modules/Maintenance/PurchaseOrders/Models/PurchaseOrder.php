<?php

namespace Modules\Maintenance\PurchaseOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Maintenance\PurchaseOrders\Enums\PurchaseOrderStatus;

class PurchaseOrder extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'supplier_id', 'status', 'reference_number', 'notes',
        'sent_at', 'received_at',
    ];

    protected $casts = [
        'status'      => PurchaseOrderStatus::class,
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
    ];

    public function uniqueIds(): array { return ['id']; }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
