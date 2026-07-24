<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'company_id', 'agent_id', 'call_log_id',
        'customer_name', 'customer_phone', 'customer_email',
        'starts_at', 'ends_at', 'status', 'provider',
        'external_event_id', 'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>=', now())->where('status', '!=', 'cancelled');
    }
}
