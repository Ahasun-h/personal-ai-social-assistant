<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'operation_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost_usd',
        'latency_ms',
        'status',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost_usd' => 'decimal:5',
        'latency_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getCurrentMonthSpend(string $userId): float
    {
        return (float) static::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('estimated_cost_usd');
    }

    public static function getTodaySpend(string $userId): float
    {
        return (float) static::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('estimated_cost_usd');
    }
}
