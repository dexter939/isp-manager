<?php

declare(strict_types=1);

namespace Modules\Contracts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contracts\Enums\CustomerStatus;
use Modules\Contracts\Enums\CustomerType;
use Modules\Contracts\Enums\PaymentMethod;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'type', 'ragione_sociale', 'nome', 'cognome',
        'codice_fiscale', 'piva', 'email', 'pec', 'telefono', 'cellulare',
        'indirizzo_fatturazione', 'payment_method', 'iban',
        'stripe_customer_id', 'sepa_mandate_id', 'status', 'notes',
    ];

    protected $casts = [
        // Dati sensibili cifrati a riposo (AES-256 via Laravel Crypt)
        'codice_fiscale'       => 'encrypted',
        'piva'                 => 'encrypted',
        'iban'                 => 'encrypted',

        // Enums tipizzati
        'type'           => CustomerType::class,
        'status'         => CustomerStatus::class,
        'payment_method' => PaymentMethod::class,

        // JSON
        'indirizzo_fatturazione' => 'array',
    ];

    /** Audit trail: logga solo campi rilevanti, mai dati cifrati */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'status', 'payment_method', 'email', 'pec'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('customers');
    }

    // ---- Relazioni ----

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function activeContracts(): HasMany
    {
        return $this->contracts()->where('status', ContractStatus::Active->value);
    }

    // ---- Accessors ----

    /** Nome completo o ragione sociale */
    public function getFullNameAttribute(): string
    {
        return $this->type === CustomerType::Azienda
            ? ($this->ragione_sociale ?? '')
            : trim("{$this->nome} {$this->cognome}");
    }

    // ---- Scopes ----

    public function scopeActive($query): mixed
    {
        return $query->where('status', CustomerStatus::Active->value);
    }

    public function scopeOfType($query, CustomerType $type): mixed
    {
        return $query->where('type', $type->value);
    }

    public function scopeSearch($query, string $term): mixed
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ragione_sociale', 'ilike', "%{$term}%")
              ->orWhere('nome', 'ilike', "%{$term}%")
              ->orWhere('cognome', 'ilike', "%{$term}%")
              ->orWhere('email', 'ilike', "%{$term}%");
        });
    }
}
