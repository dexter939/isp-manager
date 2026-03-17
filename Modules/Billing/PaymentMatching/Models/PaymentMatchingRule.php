<?php
namespace Modules\Billing\PaymentMatching\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Billing\PaymentMatching\Enums\MatchingAction;
class PaymentMatchingRule extends Model {
    use HasUuids;
    protected $table = 'payment_matching_rules';
    protected $guarded = ['id'];
    protected $casts = [
        'criteria'  => 'array',
        'action'    => MatchingAction::class,
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];
    public function uniqueIds(): array { return ['id']; }
    public function scopeActive($query) { return $query->where('is_active', true)->orderBy('priority'); }
}
