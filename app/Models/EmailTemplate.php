<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'tenant_id', 'slug', 'name', 'subject',
        'body_html', 'body_text', 'is_active', 'variables',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array',
    ];
}
