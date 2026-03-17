<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'customer_id', 'conversation_id',
        'waba_message_id', 'direction', 'from_number', 'to_number',
        'message_type', 'body', 'template_name', 'template_params',
        'status', 'error_message',
        'sent_at', 'delivered_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'template_params' => 'array',
            'sent_at'         => 'datetime',
            'delivered_at'    => 'datetime',
            'read_at'         => 'datetime',
        ];
    }

    public function markDelivered(): void
    {
        $this->update(['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function markRead(): void
    {
        $this->update(['status' => 'read', 'read_at' => now()]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error_message' => $error]);
    }
}
