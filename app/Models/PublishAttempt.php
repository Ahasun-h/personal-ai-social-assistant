<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublishAttempt extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'publish_attempts';

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'schedule_id',
        'draft_id',
        'social_account_id',
        'attempt_number',
        'status',
        'idempotency_key',
        'external_post_id',
        'external_post_url',
        'error_code',
        'error_message',
        'payload_sent',
        'response_received',
        'attempted_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'payload_sent' => 'array',
        'response_received' => 'array',
        'attempted_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(MetricsSnapshot::class);
    }
}
