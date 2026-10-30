<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricsSnapshot extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'metrics_snapshots';

    protected $fillable = [
        'social_account_id',
        'publish_attempt_id',
        'external_post_id',
        'impressions',
        'reach',
        'engagements',
        'likes',
        'retweets_reposts',
        'comments_count',
        'clicks',
        'engagement_rate',
        'raw_metrics',
        'captured_at',
    ];

    protected $casts = [
        'impressions' => 'integer',
        'reach' => 'integer',
        'engagements' => 'integer',
        'likes' => 'integer',
        'retweets_reposts' => 'integer',
        'comments_count' => 'integer',
        'clicks' => 'integer',
        'engagement_rate' => 'decimal:4',
        'raw_metrics' => 'array',
        'captured_at' => 'datetime',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function publishAttempt(): BelongsTo
    {
        return $this->belongsTo(PublishAttempt::class);
    }
}
