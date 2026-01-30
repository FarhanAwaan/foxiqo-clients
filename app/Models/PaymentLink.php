<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLink extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'invoice_id', 'provider', 'provider_reference',
        'payment_url', 'amount', 'status', 'sent_at',
        'sent_manually', 'paid_at', 'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'sent_manually' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }
}
