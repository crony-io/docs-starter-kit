<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        return $setting?->value ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        self::clearAllCaches($key, $group);

        return $setting;
    }

    public static function clearAllCaches(?string $key = null, ?string $group = null): void
    {
        if ($key) {
            Cache::forget("setting.{$key}");
        }
        if ($group) {
            Cache::forget("settings.group.{$group}");
        }
        Cache::forget('settings.all');
    }

    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return self::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function getCached(): array
    {
        return Cache::remember('settings.all', 3600, function () {
            return self::all()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function theme(): array
    {
        return self::getByGroup('theme');
    }

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => $setting->clearRelatedCaches());
        static::deleted(fn (self $setting) => $setting->clearRelatedCaches());
    }

    private function clearRelatedCaches(): void
    {
        self::clearAllCaches($this->key, $this->group);
    }
}
