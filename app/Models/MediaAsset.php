<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\MediaGenerationRequest;
use App\Models\PostMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'media_assets';

    protected $fillable = [
        'user_id',
        'filename',
        'disk',
        'path',
        'mime_type',
        'file_size_bytes',
        'width',
        'height',
        'duration_seconds',
        'alt_text',
        'is_ai_generated',
        'metadata',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration_seconds' => 'integer',
        'is_ai_generated' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postMedia(): HasMany
    {
        return $this->hasMany(PostMedia::class);
    }

    public function getUrl(): string
    {
        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isDocument(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    protected static function booted(): void
    {
        static::deleting(function (MediaAsset $asset) {
            // Delete post_media pivot rows referencing this asset
            PostMedia::where('media_asset_id', $asset->id)->delete();

            // Dissociate from any media generation requests
            MediaGenerationRequest::where('result_asset_id', $asset->id)->update(['result_asset_id' => null]);

            // Clean up physical file on disk if stored locally
            try {
                if ($asset->path && str_starts_with($asset->path, '/storage/')) {
                    $rel = preg_replace('#^/storage/#', '', $asset->path);
                    Storage::disk('public')->delete($rel);
                } elseif ($asset->path && !str_starts_with($asset->path, 'http') && Storage::disk($asset->disk ?? 'public')->exists($asset->path)) {
                    Storage::disk($asset->disk ?? 'public')->delete($asset->path);
                }
            } catch (\Throwable $e) {
                // Ignore physical file cleanup failures
            }
        });
    }
}
