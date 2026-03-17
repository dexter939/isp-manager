<?php

namespace Modules\Maintenance\PurchaseOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'vat_number', 'address'];

    public function uniqueIds(): array { return ['id']; }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function reorderRules(): HasMany
    {
        return $this->hasMany(ReorderRule::class);
    }
}
