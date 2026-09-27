<?php

namespace App\Models;

use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    use HasUuid;

    /** Setup fee and/or a monthly retainer (optionally starting after a trial). */
    public const MODE_RECURRING = 'recurring';

    /** One-time charge only; the retainer is agreed later as a child (retainer) deal. */
    public const MODE_SETUP_ONLY = 'setup_only';

    protected $fillable = [
        'uuid', 'closer_id', 'company_id', 'subscription_id', 'parent_deal_id',
        'customer_name', 'business_name', 'industry', 'city', 'country',
        'phone', 'email', 'website',
        'agreed_monthly_price', 'activation_price', 'billing_mode',
        'is_trial', 'trial_days',
        'status', 'paddle_transaction_id', 'paddle_subscription_id',
        // Mirror of the Paddle subscription — written by PaddleLifecycleService only.
        'paddle_status', 'trial_ends_at', 'next_billed_at',
        'paddle_period_start', 'paddle_period_end',
        'paddle_scheduled_change', 'paddle_scheduled_change_at', 'paddle_canceled_at',
        'trial_reminded_for', 'charge_overdue_alerted_for', 'link_emailed_at',
    ];

    protected $casts = [
        'agreed_monthly_price' => 'decimal:2',
        'activation_price' => 'decimal:2',
        'is_trial' => 'boolean',
        'trial_ends_at' => 'datetime',
        'next_billed_at' => 'datetime',
        'paddle_period_start' => 'datetime',
        'paddle_period_end' => 'datetime',
        'paddle_scheduled_change_at' => 'datetime',
        'paddle_canceled_at' => 'datetime',
        'trial_reminded_for' => 'datetime',
        'charge_overdue_alerted_for' => 'datetime',
        'link_emailed_at' => 'datetime',
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

    /** The setup-only deal this retainer deal was created from, if any. */
    public function parentDeal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_deal_id');
    }

    /** Retainer deals created from this (setup-only) deal. */
    public function childDeals(): HasMany
    {
        return $this->hasMany(self::class, 'parent_deal_id');
    }

    /** Invoices this deal produced before a Subscription existed (activation fee, early retainer charge). */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isSetupOnly(): bool
    {
        return $this->billing_mode === self::MODE_SETUP_ONLY;
    }

    public function hasRecurring(): bool
    {
        return !$this->isSetupOnly();
    }

    public function isRetainerDeal(): bool
    {
        return $this->parent_deal_id !== null;
    }

    /**
     * Whether this deal's price/trial terms have already been applied to a real
     * Subscription — a deal can only fund one Agent's subscription.
     */
    public function isClaimed(): bool
    {
        return $this->subscription_id !== null;
    }

    /**
     * What Paddle charges at checkout: the setup fee, plus the first month unless a
     * trial defers it. The checkout page, emails and Paddle all have to agree on this.
     */
    public function dueToday(): float
    {
        $due = (float) $this->activation_price;

        if ($this->hasRecurring() && !$this->is_trial) {
            $due += (float) $this->agreed_monthly_price;
        }

        return round($due, 2);
    }

    /**
     * The paid deal that carries the recurring Paddle subscription for an assistant
     * created from this deal: this deal itself when it has a retainer, otherwise a
     * paid retainer deal created from it. Null when there's nothing to fund it yet
     * (a setup-only deal whose retainer hasn't been agreed and paid).
     */
    public function fundingDeal(): ?self
    {
        $candidates = collect([$this])->merge($this->childDeals()->get());

        return $candidates->first(
            fn (self $deal) => $deal->status === 'paid' && $deal->hasRecurring() && !$deal->isClaimed()
        );
    }

    public function isTrialing(): bool
    {
        return $this->paddle_status === 'trialing';
    }

    public function isPaddleManaged(): bool
    {
        return $this->paddle_subscription_id !== null;
    }

    public function hasScheduledCancellation(): bool
    {
        return $this->paddle_scheduled_change === 'cancel';
    }

    /** The moment the card on file is next charged for the retainer (the trial's end while trialing). */
    public function nextChargeAt(): ?Carbon
    {
        return $this->isTrialing() ? ($this->trial_ends_at ?? $this->next_billed_at) : $this->next_billed_at;
    }

    public function getPaddleStatusLabelAttribute(): ?string
    {
        if ($this->status !== 'paid') {
            return null;
        }

        if ($this->isSetupOnly()) {
            return 'Setup only';
        }

        return match ($this->paddle_status) {
            'trialing' => 'Free trial',
            'active' => 'Active',
            'past_due' => 'Past due',
            'paused' => 'Paused',
            'canceled' => 'Canceled',
            default => null,
        };
    }

    public function getPaddleStatusBadgeAttribute(): string
    {
        if ($this->isSetupOnly() && $this->status === 'paid') {
            return 'bg-secondary-lt';
        }

        return match ($this->paddle_status) {
            'trialing' => 'bg-purple-lt',
            'active' => 'bg-green-lt',
            'past_due' => 'bg-red-lt',
            'paused' => 'bg-yellow-lt',
            'canceled' => 'bg-red-lt',
            default => 'bg-secondary-lt',
        };
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * A company flagged purely for demo purposes is invisible to every automated billing
     * query. Only meaningful once a deal has a company (i.e. it's paid) — see
     * PaddleLifecycleService::reconcile()'s own query for the pre-payment case, where a deal
     * may have no company yet and can't be demo.
     */
    public function scopeExcludingDemo($query)
    {
        return $query->whereHas('company', fn ($q) => $q->where('is_demo', false));
    }

    /**
     * A Paddle charge date more than the grace period in the past with no charge recorded — whatever
     * Paddle's status says: still trialing/active (Paddle hasn't billed it, or billed something nobody
     * recorded) or past_due (a failed payment, which never produces a portal invoice, so nothing else on
     * Billing & Usage would show it). Left out: paused/canceled, and anything with a scheduled change —
     * no charge is due on that date. Judged from the Paddle mirror, so it's only as current as the last
     * sync (paddle:reconcile refreshes it every few minutes). Keep in step with isChargeOverdue().
     */
    public function scopeChargeOverdue($query)
    {
        return $query->where('status', 'paid')
            ->whereNotNull('paddle_subscription_id')
            ->whereIn('paddle_status', ['trialing', 'active', 'past_due'])
            ->whereNull('paddle_scheduled_change')
            ->whereNotNull('next_billed_at')
            ->where('next_billed_at', '<', now()->subHours(self::chargeOverdueGraceHours()));
    }

    /** The scope above, for a single deal already in memory. */
    public function isChargeOverdue(): bool
    {
        return $this->status === 'paid'
            && $this->paddle_subscription_id !== null
            && in_array($this->paddle_status, ['trialing', 'active', 'past_due'], true)
            && $this->paddle_scheduled_change === null
            && $this->next_billed_at !== null
            && $this->next_billed_at->lt(now()->subHours(self::chargeOverdueGraceHours()));
    }

    public static function chargeOverdueGraceHours(): int
    {
        return (int) config('billing.charge_overdue_grace_hours', 48);
    }

    /**
     * Trialing deals whose first charge is within $days and that haven't been
     * reminded for that exact date yet. A date moved by an extension no longer
     * matches trial_reminded_for, so the reminder re-arms itself. Deals already
     * set to cancel are skipped — nothing is going to be charged.
     */
    public function scopeTrialReminderDue($query, int $days)
    {
        return $query->where('status', 'paid')
            ->where('paddle_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->where('trial_ends_at', '<=', now()->addDays($days))
            ->where(fn ($q) => $q->whereNull('paddle_scheduled_change')->orWhere('paddle_scheduled_change', '!=', 'cancel'))
            ->where(fn ($q) => $q->whereNull('trial_reminded_for')->orWhereColumn('trial_reminded_for', '!=', 'trial_ends_at'))
            ->excludingDemo();
    }
}
