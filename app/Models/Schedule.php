<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'schedules';

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'pending';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'social_account_id',
        'draft_id',
        'scheduled_for',
        'scheduled_at',
        'status',
        'lock_key',
        'locked_at',
    ];

    public function setScheduledAtAttribute($value): void
    {
        $this->attributes['scheduled_for'] = $value;
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($schedule) {
            if (empty($schedule->user_id) && !empty($schedule->draft_id)) {
                $draft = Draft::find($schedule->draft_id);
                if ($draft) {
                    $schedule->user_id = $draft->user_id;
                }
            }
        });
    }

    protected $casts = [
        'scheduled_for' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PublishAttempt::class);
    }
}
