<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Maintenance\Enums\InventoryMovementType;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'inventory_item_id', 'user_id', 'ticket_id',
        'type', 'quantity', 'quantity_before', 'quantity_after',
        'reference', 'notes', 'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'type'            => InventoryMovementType::class,
            'quantity'        => 'integer',
            'quantity_before' => 'integer',
            'quantity_after'  => 'integer',
            'moved_at'        => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TroubleTicket::class, 'ticket_id');
    }
}
