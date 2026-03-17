<?php
namespace Modules\Billing\PaymentMatching\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentMatchingLog extends Model {
    public $timestamps = false;
    protected $table = 'payment_matching_logs';
    protected $guarded = [];
    protected $casts = ['evaluation_details' => 'array', 'matched' => 'boolean', 'created_at' => 'datetime'];
    public static function boot(): void { parent::boot(); static::creating(fn($m) => $m->created_at = now()); }
}
