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
            'deal_created' => 'Deal Created',
            'deal_paid' => 'Deal Paid',
            'usage_invoice_created' => 'Usage Invoice Created',
            'paddle_renewal_reconciled' => 'Paddle Renewal Reconciled',
            'paddle_trial_converted' => 'Paddle Trial Converted to Paid',
            'paddle_payment_failed' => 'Paddle Payment Failed',
            'paddle_adjustment' => 'Paddle Refund/Adjustment',
            'paddle_charge_recorded' => 'Paddle Charge Recorded (no assistant attached yet)',
            'paddle_charge_overdue' => 'Paddle Charge Overdue',
            'usage_charge_requested' => 'Usage Charge Sent to Paddle',
            'usage_charge_collected' => 'Usage Charge Collected',
            'usage_charge_unmatched' => 'Unmatched Paddle Charge Recorded',
            'usage_charge_deferred' => 'Usage Charge Deferred (earlier one still open)',
            'usage_charge_below_minimum' => 'Usage Charge Below Paddle\'s Minimum',
            'usage_charge_request_failed' => 'Usage Charge Could Not Be Sent to Paddle',
            'usage_charge_failed' => 'Usage Charge Declined',
            'paddle_trial_extended' => 'First-Charge Date Changed',
            'paddle_billing_started' => 'Billing Started Early',
            'paddle_cancel_scheduled' => 'Cancellation Scheduled',
            'paddle_cancelled_immediately' => 'Subscription Cancelled Immediately',
            'paddle_cancel_withdrawn' => 'Scheduled Cancellation Undone',
            'deal_payment_failed' => 'Checkout Payment Attempt Failed',
            'deal_voided' => 'Deal Voided',
            'user_access_updated' => 'Customer & Assistant Access Updated',
            'user_permissions_updated' => 'Permissions Updated',
            'role_updated' => 'Role Updated',
            'permission_created' => 'Permission Created',
            'setting_revealed' => 'Secret Revealed',
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
            'invoice_created', 'payment_link_sent', 'usage_invoice_created' => 'text-primary',
            'payment_received', 'paddle_renewal_reconciled', 'paddle_trial_converted', 'paddle_charge_recorded', 'paddle_billing_started', 'paddle_cancel_withdrawn', 'deal_paid' => 'text-success',
            'paddle_payment_failed', 'paddle_charge_overdue', 'paddle_adjustment', 'paddle_cancelled_immediately', 'deal_payment_failed', 'deal_voided', 'usage_charge_request_failed', 'usage_charge_failed' => 'text-danger',
            'usage_charge_requested', 'usage_charge_collected' => 'text-success',
            'paddle_trial_extended', 'paddle_cancel_scheduled', 'usage_charge_unmatched', 'usage_charge_deferred', 'usage_charge_below_minimum' => 'text-warning',
            'setting_revealed' => 'text-warning',
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
