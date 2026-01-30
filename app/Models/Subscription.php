<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'agent_id', 'company_id', 'plan_id', 'status', 'custom_price',
        'current_period_start', 'current_period_end', 'minutes_used',
        'circuit_breaker_triggered', 'circuit_breaker_triggered_at',
        'activated_at', 'expires_at', 'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'circuit_breaker_triggered' => 'boolean',
        'circuit_breaker_triggered_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function billingCycles(): HasMany
    {
        return $this->hasMany(BillingCycle::class);
    }

    public function getEffectivePrice(): float
    {
        return $this->custom_price ?? $this->plan->price;
    }

    public function getUsagePercentage(): float
    {
        if ($this->plan->included_minutes === 0) return 0;
        return round(($this->minutes_used / $this->plan->included_minutes) * 100, 2);
    }

    public function isNearLimit(): bool
    {
        return $this->getUsagePercentage() >= 80;
    }

    public function isOverLimit(): bool
    {
        return $this->minutes_used > $this->plan->included_minutes;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('current_period_end', [now(), now()->addDays($days)]);
    }
}
