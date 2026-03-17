<?php

namespace Modules\Maintenance\PurchaseOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Brick\Money\Money;

class PurchaseOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'purchase_order_id', 'inventory_model_id',
        'quantity_ordered', 'quantity_received',
        'unit_price_amount', 'unit_price_currency',
    ];

    public function uniqueIds(): array { return ['id']; }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getUnitPriceAttribute(): ?Money
    {
        if ($this->unit_price_amount === null) return null;
        return Money::ofMinor($this->unit_price_amount, $this->unit_price_currency);
    }
}
