<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'role', 'content',
        'input_tokens', 'output_tokens',
        'stop_reason', 'tool_use',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens'  => 'integer',
            'output_tokens' => 'integer',
            'tool_use'      => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
