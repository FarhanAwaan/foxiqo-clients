<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'company_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get a human-readable action name
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'subscription_created' => 'Subscription Created',
            'subscription_activated' => 'Subscription Activated',
            'subscription_cancelled' => 'Subscription Cancelled',
            'subscription_renewed' => 'Subscription Renewed',
            'invoice_created' => 'Invoice Created',
            'payment_link_sent' => 'Payment Link Sent',
            'payment_received' => 'Payment Received',
            'plan_created' => 'Plan Created',
            'plan_updated' => 'Plan Updated',
            'plan_deleted' => 'Plan Deleted',
            'company_created' => 'Company Created',
            'company_updated' => 'Company Updated',
            'user_created' => 'User Created',
            'user_updated' => 'User Updated',
            'agent_created' => 'Agent Created',
            'agent_updated' => 'Agent Updated',
            'login' => 'User Login',
            'logout' => 'User Logout',
            default => str_replace('_', ' ', ucfirst($this->action)),
        };
    }

    /**
     * Get the action icon class
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'subscription_created', 'subscription_activated' => 'text-success',
            'subscription_cancelled' => 'text-danger',
            'invoice_created', 'payment_link_sent' => 'text-primary',
            'payment_received' => 'text-success',
            'plan_created', 'plan_updated' => 'text-info',
            'login' => 'text-primary',
            'logout' => 'text-muted',
            default => 'text-secondary',
        };
    }

    /**
     * Get short entity type name
     */
    public function getEntityNameAttribute(): string
    {
        if (!$this->entity_type) {
            return '';
        }

        return class_basename($this->entity_type);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
