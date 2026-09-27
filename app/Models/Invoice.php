<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'invoice_number', 'subscription_id', 'deal_id', 'company_id', 'invoice_type',
        'amount', 'usage_minutes', 'status', 'billing_period_start', 'billing_period_end',
        'due_date', 'sent_at', 'paid_at', 'notes', 'paddle_transaction_id', 'paddle_status',
        'paddle_charged_amount', 'paddle_tax_amount', 'paddle_fee_amount', 'paddle_refunded_amount',
        'usage_receipt_pending_invoice_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'paddle_charged_amount' => 'decimal:2',
        'paddle_tax_amount' => 'decimal:2',
        'paddle_fee_amount' => 'decimal:2',
        'paddle_refunded_amount' => 'decimal:2',
    ];

    public function isUsageInvoice(): bool
    {
        return $this->invoice_type === 'usage';
    }

    public function isActivationInvoice(): bool
    {
        return $this->invoice_type === 'activation';
    }

    public function wentThroughPaddle(): bool
    {
        return $this->paddle_transaction_id !== null;
    }

    /**
     * Whether what Paddle's transaction says it charged agrees with our own recorded amount (plus
     * whatever tax Paddle added on top — a mismatch here is never expected to be tax, since that's
     * already accounted for). False is worth looking at: a discount, a proration, or a bug.
     * Invoices that never went through Paddle (Nsave/manual) always reconcile — there is nothing to
     * compare against.
     */
    public function reconcilesWithPaddle(): bool
    {
        if ($this->paddle_charged_amount === null) {
            return true;
        }

        $expected = (float) $this->amount + (float) ($this->paddle_tax_amount ?? 0);

        return abs((float) $this->paddle_charged_amount - $expected) < 0.01;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->invoice_type) {
            'usage' => 'Usage',
            'activation' => 'Activation',
            default => 'Retainer',
        };
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** Set on a retainer invoice while its usage-overage sibling's charge is still awaiting Paddle. */
    public function pendingUsageInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'usage_receipt_pending_invoice_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function pendingReceipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class)->where('status', 'pending');
    }

    public function hasPendingReceipt(): bool
    {
        return $this->receipts()->where('status', 'pending')->exists();
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date < now();
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'overdue']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('due_date', '<', now());
    }
}
