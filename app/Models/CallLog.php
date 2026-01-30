<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'agent_id', 'retell_call_id', 'call_status', 'direction',
        'from_number', 'to_number', 'started_at', 'ended_at',
        'duration_seconds', 'duration_minutes', 'retell_cost',
        'transcript', 'summary', 'sentiment', 'recording_url',
        'metadata', 'analyzed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'analyzed_at' => 'datetime',
        'duration_minutes' => 'decimal:2',
        'retell_cost' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function getTranscriptArrayAttribute(): array
    {
        if (empty($this->transcript)) return [];
        return json_decode($this->transcript, true) ?? [];
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) return '0:00';
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('call_status', 'analyzed');
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->whereBetween('started_at', [$start, $end]);
    }
}
