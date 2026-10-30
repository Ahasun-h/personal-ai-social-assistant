<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Draft extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'drafts';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'category_id',
        'trend_item_id',
        'title',
        'status',
        'scheduled_at',
        'published_at',
        'target_platforms',
        'ai_prompt_context',
        'is_evergreen',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'target_platforms' => 'array',
        'is_evergreen' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InterestCategory::class, 'category_id');
    }

    public function trendItem(): BelongsTo
    {
        return $this->belongsTo(TrendItem::class, 'trend_item_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DraftVersion::class)->orderBy('version_number', 'desc');
    }

    public function postMedia(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('sort_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function publishAttempts(): HasMany
    {
        return $this->hasMany(PublishAttempt::class);
    }

    public function latestVersion(?string $platform = null): ?DraftVersion
    {
        $query = $this->versions();
        if ($platform) {
            $query->whereIn('platform', [$platform, 'universal']);
        }
        return $query->first();
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_SCHEDULED, self::STATUS_PUBLISHING, self::STATUS_PUBLISHED]);
    }

    public function canPublish(): bool
    {
        return $this->isApproved() && $this->status !== self::STATUS_PUBLISHING;
    }
}
