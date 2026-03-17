<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'type',
        'quantity',
        'unit_price',
        'tax_rate',
        'total_net',
        'total_tax',
        'total_gross',
        'natura_iva',
        'codice_articolo',
        'period_from',
        'period_to',
        'sort_order',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'unit_price'  => 'decimal:2',
        'tax_rate'    => 'decimal:2',
        'total_net'   => 'decimal:2',
        'total_tax'   => 'decimal:2',
        'total_gross' => 'decimal:2',
        'period_from' => 'date',
        'period_to'   => 'date',
        'sort_order'  => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calcola i totali della riga a partire da qty e unit_price.
     */
    public static function computeTotals(int $qty, string $unitPrice, string $taxRate): array
    {
        $net   = bcmul((string) $qty, $unitPrice, 2);
        $tax   = bcdiv(bcmul($net, $taxRate, 4), '100', 2);
        $gross = bcadd($net, $tax, 2);

        return [
            'total_net'   => $net,
            'total_tax'   => $tax,
            'total_gross' => $gross,
        ];
    }
}
