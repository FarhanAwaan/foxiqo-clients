<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuid;

    /**
     * Email types whose body is never stored: they carry a private, single-use link (account
     * setup, password reset), and a copy sitting in the database would be a second place to
     * steal it from. The envelope (who/when) is still recorded.
     */
    public const UNSTORED_CONTENT_TYPES = ['password_reset', 'user_invitation'];

    public $timestamps = false;

    protected $fillable = [
        'uuid', 'company_id', 'user_id', 'type', 'channel', 'status', 'error',
        'subject', 'body', 'data', 'envelope', 'html_body', 'sent_at', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'envelope' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRecipientEmailAttribute(): ?string
    {
        return $this->data['recipient_email'] ?? null;
    }

    /** Whether this email's content may be kept (see UNSTORED_CONTENT_TYPES). */
    public function keepsContent(): bool
    {
        return !in_array($this->type, self::UNSTORED_CONTENT_TYPES, true);
    }

    /**
     * One header's addresses as a list of {email, name?}. Falls back to the recipient recorded
     * at queue time for emails stored before envelopes were kept.
     *
     * @return array<int, array{email: string, name?: string}>
     */
    public function addresses(string $field): array
    {
        $stored = $this->envelope[$field] ?? null;

        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        if ($field === 'to' && $this->recipient_email) {
            return [['email' => $this->recipient_email]];
        }

        return [];
    }
}
