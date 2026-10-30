<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'post_media';

    protected $fillable = [
        'draft_id',
        'draft_version_id',
        'media_asset_id',
        'platform',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    public function draftVersion(): BelongsTo
    {
        return $this->belongsTo(DraftVersion::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
