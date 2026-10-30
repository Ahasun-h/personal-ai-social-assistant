<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestCategory extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'interest_categories';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'keywords',
        'tone_guidelines',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trendSources(): HasMany
    {
        return $this->hasMany(TrendSource::class, 'category_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class, 'category_id');
    }
}
