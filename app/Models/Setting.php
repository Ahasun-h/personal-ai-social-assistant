<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'settings';

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? ($setting->value['val'] ?? $setting->value) : $default;
    }

    public static function set(string $userId, string $key, mixed $value, ?string $description = null): self
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            [
                'value' => is_array($value) ? $value : ['val' => $value],
                'description' => $description,
            ]
        );
    }
}
