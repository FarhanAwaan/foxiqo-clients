<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'closer_id', 'company_id', 'subscription_id',
        'customer_name', 'business_name', 'industry', 'city', 'country',
        'phone', 'email', 'website',
        'agreed_monthly_price', 'activation_price',
        'is_trial', 'trial_days',
        'status', 'paddle_transaction_id', 'paddle_subscription_id',
    ];

    protected $casts = [
        'agreed_monthly_price' => 'decimal:2',
        'activation_price' => 'decimal:2',
        'is_trial' => 'boolean',
    ];

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Whether this deal's price/trial terms have already been applied to a real
     * Subscription — a deal can only fund one Agent's subscription.
     */
    public function isClaimed(): bool
    {
        return $this->subscription_id !== null;
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
