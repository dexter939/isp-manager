<?php

declare(strict_types=1);

namespace Modules\Coverage\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageFibercop extends Model
{
    protected $table = 'coverage_fibercop';

    protected $fillable = [
        'codice_ui', 'comune', 'provincia', 'cap',
        'via', 'civico', 'via_normalizzata', 'civico_normalizzato',
        'tecnologia', 'velocita_max_dl', 'velocita_max_ul',
        'stato_commerciale', 'armadio_id',
        'imported_at', 'source_file',
    ];

    protected $casts = [
        'velocita_max_dl' => 'integer',
        'velocita_max_ul' => 'integer',
        'imported_at'     => 'datetime',
    ];

    public function scopeVendibile($query): mixed
    {
        return $query->where('stato_commerciale', 'vendibile');
    }

    public function scopeFtth($query): mixed
    {
        return $query->where('tecnologia', 'FTTH');
    }

    public function scopeInProvincia($query, string $provincia): mixed
    {
        return $query->where('provincia', strtoupper($provincia));
    }

    /**
     * Cerca per indirizzo normalizzato.
     */
    public function scopeAtAddress($query, string $via, string $civico, string $comune, string $provincia): mixed
    {
        return $query
            ->where('via_normalizzata', $via)
            ->where('civico_normalizzato', $civico)
            ->where('comune', $comune)
            ->where('provincia', strtoupper($provincia));
    }
}
