<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Models\Contract;

class HardwareAsset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'contract_id', 'assigned_by',
        'type', 'brand', 'model', 'serial_number', 'mac_address', 'qr_code',
        'status', 'assigned_at', 'returned_at',
        'purchase_price', 'purchase_date', 'warranty_expires', 'supplier',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price'  => 'decimal:2',
            'purchase_date'   => 'date',
            'warranty_expires' => 'date',
            'assigned_at'     => 'datetime',
            'returned_at'     => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isInStock(): bool
    {
        return $this->status === 'in_stock';
    }

    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_expires && $this->warranty_expires->isFuture();
    }

    public function scopeInStock($query)
    {
        return $query->where('status', 'in_stock');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeUnreturned($query)
    {
        return $query->assigned()->whereHas('contract', fn($q) => $q->where('status', 'terminated'));
    }
}
