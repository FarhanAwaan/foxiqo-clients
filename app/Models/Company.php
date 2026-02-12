<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'name', 'email', 'billing_email', 'phone',
        'address', 'city', 'state', 'postal_code', 'country',
        'status', 'webhook_signature', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (empty($company->webhook_signature)) {
                $company->webhook_signature = static::generateWebhookSignature();
            }
        });
    }

    public static function generateWebhookSignature(): string
    {
        return 'whsig_' . Str::random(40);
    }

    public function regenerateWebhookSignature(): string
    {
        $this->webhook_signature = static::generateWebhookSignature();
        $this->save();
        return $this->webhook_signature;
    }

    public function getWebhookUrl(): string
    {
        return url("/api/webhooks/{$this->id}/retell");
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function billingCycles(): HasMany
    {
        return $this->hasMany(BillingCycle::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the effective billing email (falls back to company email if billing_email not set)
     */
    public function getEffectiveBillingEmailAttribute(): string
    {
        return $this->billing_email ?? $this->email;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        return implode(', ', $parts);
    }
}
