<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaGenerationRequest extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'media_generation_requests';

    protected $fillable = [
        'user_id',
        'draft_id',
        'provider',
        'prompt',
        'negative_prompt',
        'aspect_ratio',
        'style',
        'status',
        'result_asset_id',
        'error_message',
        'cost_usd',
        'raw_response',
    ];

    protected $casts = [
        'cost_usd' => 'decimal:5',
        'raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    public function resultAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'result_asset_id');
    }
}
