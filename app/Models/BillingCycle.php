<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCycle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subscription_id', 'agent_id', 'company_id',
        'period_start', 'period_end', 'plan_name',
        'subscription_amount', 'included_minutes', 'minutes_used',
        'total_calls', 'retell_cost', 'profit', 'profit_margin',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'subscription_amount' => 'decimal:2',
        'retell_cost' => 'decimal:4',
        'profit' => 'decimal:4',
        'profit_margin' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
