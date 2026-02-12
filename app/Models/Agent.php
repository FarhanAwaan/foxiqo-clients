<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agent extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'company_id', 'retell_agent_id', 'name',
        'description', 'phone_number', 'agent_type', 'cost_per_minute', 'status',
    ];

    protected $casts = [
        'cost_per_minute' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription && $this->subscription->status === 'active';
    }

    public function getAgentTypeLabelAttribute(): string
    {
        return match ($this->agent_type) {
            'inbound' => 'Inbound Only',
            'outbound' => 'Outbound Only',
            default => 'Inbound & Outbound',
        };
    }

    public function getInboundCallsCountAttribute(): int
    {
        return $this->callLogs()->where('direction', 'inbound')->count();
    }

    public function getOutboundCallsCountAttribute(): int
    {
        return $this->callLogs()->where('direction', 'outbound')->count();
    }
}
