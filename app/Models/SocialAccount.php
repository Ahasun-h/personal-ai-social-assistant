<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class SocialAccount extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'social_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_account_id',
        'account_id',
        'name',
        'username',
        'avatar_url',
        'token_type',
        'access_token',
        'token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'metadata',
        'is_active',
    ];

    public function setAccountIdAttribute($value): void
    {
        $this->attributes['provider_account_id'] = $value;
    }

    public function getAccountIdAttribute(): ?string
    {
        return $this->attributes['provider_account_id'] ?? null;
    }

    public function setTokenAttribute($value): void
    {
        $this->attributes['access_token'] = encrypt($value);
    }

    public function getTokenAttribute(): ?string
    {
        return $this->access_token;
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($account) {
            if (empty($account->username)) {
                $account->username = $account->name ?? 'user';
            }
        });
    }

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function getAccessTokenAttribute($value): ?string
    {
        if (!$value) return null;
        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function setAccessTokenAttribute($value): void
    {
        if (!$value) {
            $this->attributes['access_token'] = null;
            return;
        }
        try {
            $this->attributes['access_token'] = encrypt($value);
        } catch (\Throwable $e) {
            $this->attributes['access_token'] = $value;
        }
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        if (!$value) return null;
        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function setRefreshTokenAttribute($value): void
    {
        if (!$value) {
            $this->attributes['refresh_token'] = null;
            return;
        }
        try {
            $this->attributes['refresh_token'] = encrypt($value);
        } catch (\Throwable $e) {
            $this->attributes['refresh_token'] = $value;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function publishAttempts(): HasMany
    {
        return $this->hasMany(PublishAttempt::class);
    }

    public function metricsSnapshots(): HasMany
    {
        return $this->hasMany(MetricsSnapshot::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function daysUntilExpiry(): int
    {
        if (!$this->token_expires_at) {
            return 999;
        }
        return (int) max(0, Carbon::now()->diffInDays($this->token_expires_at, false));
    }
}
