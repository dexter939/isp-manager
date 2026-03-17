<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'sku', 'name', 'category', 'description',
        'unit', 'quantity', 'quantity_reserved', 'reorder_threshold',
        'unit_cost', 'supplier', 'location', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'quantity_reserved' => 'integer',
            'reorder_threshold' => 'integer',
            'unit_cost'         => 'decimal:2',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // ── Business logic ────────────────────────────────────────────────────────

    /** Quantità fisicamente disponibile (esclude le reserve) */
    public function availableQuantity(): int
    {
        return $this->quantity - $this->quantity_reserved;
    }

    public function isLowStock(): bool
    {
        return $this->reorder_threshold > 0 && $this->quantity <= $this->reorder_threshold;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('reorder_threshold > 0 AND quantity <= reorder_threshold');
    }
}
