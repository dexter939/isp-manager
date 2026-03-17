<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'customer_id', 'contract_id', 'ticket_id',
        'channel', 'purpose', 'status',
        'model', 'total_input_tokens', 'total_output_tokens',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'total_input_tokens'  => 'integer',
            'total_output_tokens' => 'integer',
            'metadata'            => 'array',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id');
    }

    /** Aggiunge token al contatore della conversazione */
    public function addTokens(int $inputTokens, int $outputTokens): void
    {
        $this->increment('total_input_tokens', $inputTokens);
        $this->increment('total_output_tokens', $outputTokens);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
