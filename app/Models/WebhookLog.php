<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source', 'event_type', 'payload', 'headers',
        'status', 'processed_at', 'error_message', 'retry_count',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function markProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
