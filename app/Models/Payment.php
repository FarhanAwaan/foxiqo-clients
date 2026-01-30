<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'invoice_id', 'payment_link_id', 'amount',
        'provider', 'provider_transaction_id', 'status', 'paid_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }
}
