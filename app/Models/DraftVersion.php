<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DraftVersion extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'draft_versions';

    protected $fillable = [
        'draft_id',
        'version_number',
        'platform',
        'content',
        'character_count',
        'thread_items',
        'tone',
        'created_by_ai',
    ];

    protected $casts = [
        'thread_items' => 'array',
        'created_by_ai' => 'boolean',
        'character_count' => 'integer',
        'version_number' => 'integer',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    public function postMedia(): HasMany
    {
        return $this->hasMany(PostMedia::class, 'draft_version_id');
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($version) {
            $version->character_count = mb_strlen($version->content ?? '', 'UTF-8');
        });
    }
}
