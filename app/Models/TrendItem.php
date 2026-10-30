<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrendItem extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'trend_items';

    protected $fillable = [
        'trend_source_id',
        'external_id',
        'title',
        'summary',
        'content',
        'url',
        'author',
        'published_at',
        'engagement_score',
        'virality_indicators',
        'is_bookmarked',
        'is_dismissed',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'engagement_score' => 'decimal:2',
        'virality_indicators' => 'array',
        'is_bookmarked' => 'boolean',
        'is_dismissed' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->published_at)) {
                $item->published_at = now();
            }
        });
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(TrendSource::class, 'trend_source_id');
    }

    public function trendSource(): BelongsTo
    {
        return $this->belongsTo(TrendSource::class, 'trend_source_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class, 'trend_item_id');
    }

    /**
     * Compute real-time relevance & velocity ranking score.
     */
    public function calculateScore(array $userKeywords = []): float
    {
        $ageHours = max(1, now()->diffInHours($this->published_at));
        $decay = 1 / sqrt($ageHours);

        $keywordMatches = 0;
        foreach ($userKeywords as $kw) {
            if (stripos($this->title . ' ' . $this->summary, $kw) !== false) {
                $keywordMatches++;
            }
        }

        $base = (float) $this->engagement_score;
        $score = ($base * 0.4 + ($keywordMatches * 15)) * $decay;

        return round(min(100.0, max(0.0, $score)), 2);
    }
}
