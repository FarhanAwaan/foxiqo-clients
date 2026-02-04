<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'invoice_id', 'provider', 'provider_reference',
        'payment_token', 'payment_url', 'amount', 'status', 'sent_at',
        'sent_manually', 'paid_at', 'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'sent_manually' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentLink $paymentLink) {
            // Generate payment token if not set
            if (empty($paymentLink->payment_token)) {
                $paymentLink->payment_token = static::generatePaymentToken();
            }

            // Set payment URL for internal provider if not set
            if (empty($paymentLink->payment_url) && $paymentLink->provider === 'internal') {
                $paymentLink->payment_url = route('billing.payment.show', $paymentLink->payment_token);
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function latestReceipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class)->latestOfMany();
    }

    public function pendingReceipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class)->where('status', 'pending')->latestOfMany();
    }

    public function hasPendingReceipt(): bool
    {
        return $this->receipts()->pending()->exists();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isActive(): bool
    {
        return !$this->paid_at && !$this->isExpired() && $this->invoice?->status !== 'paid';
    }

    public function getInternalPaymentUrl(): string
    {
        return route('billing.payment.show', $this->payment_token);
    }

    protected static function generatePaymentToken(): string
    {
        do {
            // Generate a secure, URL-safe token
            $token = Str::random(32) . '-' . bin2hex(random_bytes(8));
        } while (static::where('payment_token', $token)->exists());

        return $token;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('paid_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}
