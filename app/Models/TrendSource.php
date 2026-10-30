<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrendSource extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'trend_sources';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'source_type',
        'driver',
        'url',
        'source_identifier',
        'fetch_frequency_minutes',
        'last_fetched_at',
        'status',
        'is_active',
        'config',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($source) {
            if (empty($source->source_type)) {
                $source->source_type = 'hackernews';
            }
            if (empty($source->url)) {
                $source->url = 'https://news.ycombinator.com';
            }
        });
    }

    public function setDriverAttribute($value): void
    {
        $this->attributes['source_type'] = $value;
    }

    public function getDriverAttribute(): ?string
    {
        return $this->attributes['source_type'] ?? null;
    }

    public function setSourceIdentifierAttribute($value): void
    {
        $this->attributes['url'] = $value;
    }

    public function getSourceIdentifierAttribute(): ?string
    {
        return $this->attributes['url'] ?? null;
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? 'active' : 'inactive';
    }

    protected $casts = [
        'last_fetched_at' => 'datetime',
        'config' => 'array',
        'fetch_frequency_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InterestCategory::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TrendItem::class, 'trend_source_id');
    }
}
