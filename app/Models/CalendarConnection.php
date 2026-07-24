<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarConnection extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'agent_id', 'provider', 'credentials',
        'status', 'last_error', 'connected_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'connected_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
