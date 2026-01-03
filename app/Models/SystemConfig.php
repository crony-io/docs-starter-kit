<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemConfig extends Model
{
    protected $table = 'system_config';

    protected $fillable = [
        'content_mode',
        'git_repository_url',
        'git_branch',
        'git_access_token',
        'git_webhook_secret',
        'git_sync_frequency',
        'last_synced_at',
        'setup_completed',
        'web_cron_enabled',
        'last_web_cron_at',
    ];

    protected function casts(): array
    {
        return [
            'git_access_token' => 'encrypted',
            'git_webhook_secret' => 'encrypted',
            'last_synced_at' => 'datetime',
            'setup_completed' => 'boolean',
            'git_sync_frequency' => 'integer',
            'web_cron_enabled' => 'boolean',
            'last_web_cron_at' => 'datetime',
        ];
    }

    public static function instance(): self
    {
        return Cache::remember('system_config', 3600, function () {
            return self::firstOrCreate([], [
                'content_mode' => 'cms',
                'setup_completed' => false,
            ]);
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('system_config');
    }

    public static function isGitMode(): bool
    {
        return self::instance()->content_mode === 'git';
    }

    public static function isCmsMode(): bool
    {
        return self::instance()->content_mode === 'cms';
    }

    public static function gitSyncFrequency(): int
    {
        return self::instance()->git_sync_frequency ?? 15;
    }

    public static function isSetupCompleted(): bool
    {
        return self::instance()->setup_completed;
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }
}
