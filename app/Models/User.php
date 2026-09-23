<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

use App\Traits\HasUuid;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuid, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'company_id', 'first_name', 'last_name', 'email', 'phone',
        'password', 'role', 'status', 'email_verified_at', 'two_factor_enabled',
        'two_factor_secret', 'signup_token', 'signup_token_expires_at', 'last_login_at',
        'password_reset_token', 'password_reset_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'signup_token', 'password_reset_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'signup_token_expires_at' => 'datetime',
            'password_reset_token_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isCloser(): bool
    {
        return $this->role === 'closer';
    }

    /**
     * Companies an admin has explicitly granted this user (closer/manager) visibility
     * into. Admin and customer users don't use this — admin already sees everything,
     * customer is already scoped to their own company_id.
     */
    public function accessibleCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user_access');
    }

    /**
     * Optional per-agent narrowing within an accessible company — see
     * agent_user_access migration for the "no rows = full access" default.
     */
    public function accessibleAgents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_user_access');
    }

    /**
     * Whether this user (assumed non-admin) may view the given company at all.
     */
    public function canAccessCompany(Company $company): bool
    {
        return $this->isAdmin() || $this->accessibleCompanies()->where('companies.id', $company->id)->exists();
    }

    /**
     * Whether this user (assumed non-admin) may view the given agent — true if they
     * can see its company AND (they have no agent-level restriction for that company,
     * or this specific agent is one of the ones they're restricted to).
     */
    public function canAccessAgent(Agent $agent): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->canAccessCompany($agent->company)) {
            return false;
        }

        $restrictedAgentIds = $this->accessibleAgents()
            ->where('agents.company_id', $agent->company_id)
            ->pluck('agents.id');

        return $restrictedAgentIds->isEmpty() || $restrictedAgentIds->contains($agent->id);
    }

    /**
     * Agents visible to this user within a company they can already access —
     * every agent if unrestricted, otherwise only the granted ones.
     */
    public function visibleAgentsIn(Company $company)
    {
        $restrictedAgentIds = $this->accessibleAgents()
            ->where('agents.company_id', $company->id)
            ->pluck('agents.id');

        return $restrictedAgentIds->isEmpty()
            ? $company->agents()->get()
            : $company->agents()->whereIn('id', $restrictedAgentIds)->get();
    }

    /**
     * The human-facing name for this user's role — admin-editable from
     * Admin > Roles & Permissions (e.g. "Closer" renamed to "Manager").
     * `role` itself never changes; only what it's displayed as does.
     */
    public function getRoleLabelAttribute(): string
    {
        return Role::where('name', $this->role)->value('label') ?: ucfirst($this->role);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function generateSignupToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $this->update([
            'signup_token' => $token,
            'signup_token_expires_at' => now()->addDays(7),
        ]);
        return $token;
    }

    public function generatePasswordResetToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $this->update([
            'password_reset_token' => $token,
            'password_reset_token_expires_at' => now()->addHours(24),
        ]);
        return $token;
    }
}
