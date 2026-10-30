<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'comments';

    protected $fillable = [
        'social_account_id',
        'external_post_id',
        'external_comment_id',
        'author_name',
        'author_handle',
        'author_avatar_url',
        'content',
        'sentiment',
        'ai_suggested_reply',
        'reply_status',
        'reply_external_id',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
