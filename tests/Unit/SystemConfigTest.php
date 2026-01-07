<?php

namespace Tests\Unit;

use App\Models\SystemConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SystemConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemConfig::clearCache();
    }

    public function test_instance_returns_singleton()
    {
        $config1 = SystemConfig::instance();
        $config2 = SystemConfig::instance();

        $this->assertEquals($config1->id, $config2->id);
    }

    public function test_instance_creates_default_config_if_none_exists()
    {
        $config = SystemConfig::instance();

        $this->assertEquals('cms', $config->content_mode);
        $this->assertFalse($config->setup_completed);
    }

    public function test_is_git_mode_returns_correct_value()
    {
        SystemConfig::create([
            'content_mode' => 'git',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $this->assertTrue(SystemConfig::isGitMode());
        $this->assertFalse(SystemConfig::isCmsMode());
    }

    public function test_is_cms_mode_returns_correct_value()
    {
        SystemConfig::create([
            'content_mode' => 'cms',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $this->assertTrue(SystemConfig::isCmsMode());
        $this->assertFalse(SystemConfig::isGitMode());
    }

    public function test_git_sync_frequency_returns_value()
    {
        SystemConfig::create([
            'content_mode' => 'git',
            'git_sync_frequency' => 30,
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $this->assertEquals(30, SystemConfig::gitSyncFrequency());
    }

    public function test_git_sync_frequency_defaults_to_15()
    {
        SystemConfig::create([
            'content_mode' => 'git',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $this->assertEquals(15, SystemConfig::gitSyncFrequency());
    }

    public function test_is_setup_completed_returns_correct_value()
    {
        SystemConfig::create([
            'content_mode' => 'cms',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $this->assertTrue(SystemConfig::isSetupCompleted());
    }

    public function test_clear_cache_removes_cached_config()
    {
        $config = SystemConfig::instance();

        // Verify cache exists
        Cache::put('system_config', $config, 3600);
        $this->assertTrue(Cache::has('system_config'));

        SystemConfig::clearCache();

        // Verify cache was cleared
        $this->assertFalse(Cache::has('system_config'));
    }

    public function test_git_access_token_is_encrypted()
    {
        $config = SystemConfig::create([
            'content_mode' => 'git',
            'git_access_token' => 'secret-token',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $rawValue = $config->getRawOriginal('git_access_token');

        $this->assertNotEquals('secret-token', $rawValue);

        $config->refresh();
        $this->assertEquals('secret-token', $config->git_access_token);
    }

    public function test_git_webhook_secret_is_encrypted()
    {
        $config = SystemConfig::create([
            'content_mode' => 'git',
            'git_webhook_secret' => 'webhook-secret',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $rawValue = $config->getRawOriginal('git_webhook_secret');

        $this->assertNotEquals('webhook-secret', $rawValue);

        $config->refresh();
        $this->assertEquals('webhook-secret', $config->git_webhook_secret);
    }

    public function test_saving_config_clears_cache()
    {
        $config = SystemConfig::create([
            'content_mode' => 'cms',
            'setup_completed' => false,
        ]);

        SystemConfig::clearCache();

        $config->update(['setup_completed' => true]);

        $freshConfig = SystemConfig::instance();
        $this->assertTrue($freshConfig->setup_completed);
    }
}
