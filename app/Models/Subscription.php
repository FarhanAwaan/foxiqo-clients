<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'agent_id', 'company_id', 'plan_id', 'status', 'custom_price',
        'paddle_subscription_id',
        'current_period_start', 'current_period_end', 'minutes_used',
        'circuit_breaker_triggered', 'circuit_breaker_triggered_at',
        'activated_at', 'expires_at', 'cancelled_at', 'cancellation_reason',
        'is_trial', 'trial_days', 'trial_ends_at', 'trial_ending_warned',
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
        'is_trial' => 'boolean',
        'trial_ends_at' => 'datetime',
        'trial_ending_warned' => 'boolean',
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

    /** The Deal whose terms funded this subscription (deals.subscription_id), if it came from one. */
    public function deal(): HasOne
    {
        return $this->hasOne(Deal::class);
    }

    /**
     * Paddle-linked: Paddle — not this app's own cron — decides when the retainer is
     * charged and when periods roll. See PaddleLifecycleService.
     */
    public function isPaddleManaged(): bool
    {
        return $this->paddle_subscription_id !== null;
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

    public function isTrial(): bool
    {
        return (bool) $this->is_trial;
    }

    public function isTrialActive(): bool
    {
        return $this->is_trial && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isTrialExpired(): bool
    {
        return $this->is_trial && $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->is_trial || !$this->trial_ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * A company flagged purely for demo purposes is invisible to every automated billing
     * query — see PaddleLifecycleService::isDemo() for the Paddle-managed side of the same rule.
     */
    public function scopeExcludingDemo($query)
    {
        return $query->whereHas('company', fn ($q) => $q->where('is_demo', false));
    }

    /**
     * "Expiring soon" warnings for subscriptions this app renews itself. Paddle-managed
     * ones auto-renew on Paddle's schedule, so an "expires on X" email would be wrong.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->where('is_trial', false)
            ->whereNull('paddle_subscription_id')
            ->whereBetween('current_period_end', [now(), now()->addDays($days)])
            ->excludingDemo();
    }

    /**
     * Subscriptions whose period this app's own renewal cron rolls. Paddle-managed ones
     * are excluded: their period only ever advances when Paddle actually charges (see
     * SubscriptionService::recordPaddleCharge()), so the two clocks can't drift apart.
     */
    public function scopeDueForInternalRenewal($query)
    {
        return $query->where('status', 'active')
            ->whereNull('paddle_subscription_id')
            ->where('current_period_end', '<', now())
            ->excludingDemo();
    }

    /**
     * Trials this app's own cron should convert to paid + invoice directly.
     * Excludes Paddle-linked trials — Paddle owns that clock and charges the
     * customer's card itself; the renewal webhook reconciles those when Paddle
     * actually bills, not on this app's trial_days countdown.
     */
    public function scopeTrialExpired($query)
    {
        return $query->where('status', 'active')
            ->where('is_trial', true)
            ->whereNull('paddle_subscription_id')
            ->where('trial_ends_at', '<=', now())
            ->excludingDemo();
    }

    /**
     * Trials this app's own "ending soon" email covers. Paddle trials get their own
     * accurate reminder (the card is charged automatically — no invoice is sent) from
     * `paddle:send-trial-reminders`, so they're excluded here.
     */
    public function scopeTrialEndingSoon($query, int $days = 3)
    {
        return $query->where('status', 'active')
            ->where('is_trial', true)
            ->whereNull('paddle_subscription_id')
            ->where('trial_ending_warned', false)
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)])
            ->excludingDemo();
    }
}
