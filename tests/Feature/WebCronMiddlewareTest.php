<?php

namespace Tests\Feature;

use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebCronMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        SystemConfig::clearCache();

        // Setup requires at least one user to be "complete"
        User::factory()->create();
    }

    private function createConfig(array $overrides = []): SystemConfig
    {
        $config = SystemConfig::create(array_merge([
            'content_mode' => 'cms',
            'setup_completed' => true,
            'web_cron_enabled' => false,
            'last_web_cron_at' => null,
        ], $overrides));

        SystemConfig::clearCache();

        return $config;
    }

    private function createAdminUser(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_middleware_does_not_trigger_when_disabled(): void
    {
        $this->createConfig(['web_cron_enabled' => false]);

        $this->get('/');

        $config = SystemConfig::instance();
        SystemConfig::clearCache();

        $this->assertNull($config->fresh()->last_web_cron_at);
    }

    public function test_middleware_updates_timestamp_when_enabled(): void
    {
        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => null,
        ]);

        $this->get('/');

        SystemConfig::clearCache();
        $config = SystemConfig::instance();

        $this->assertNotNull($config->last_web_cron_at);
    }

    public function test_middleware_throttles_within_60_seconds(): void
    {
        $initialTime = now()->subSeconds(30);

        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => $initialTime,
        ]);

        $this->get('/');

        SystemConfig::clearCache();
        $config = SystemConfig::instance();

        $this->assertEquals(
            $initialTime->toDateTimeString(),
            $config->last_web_cron_at->toDateTimeString()
        );
    }

    public function test_middleware_triggers_after_60_seconds(): void
    {
        $initialTime = now()->subSeconds(61);

        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => $initialTime,
        ]);

        $this->get('/');

        SystemConfig::clearCache();
        $config = SystemConfig::instance();

        $this->assertNotEquals(
            $initialTime->toDateTimeString(),
            $config->last_web_cron_at->toDateTimeString()
        );
    }

    public function test_middleware_skips_non_get_requests(): void
    {
        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => null,
        ]);

        $user = $this->createAdminUser();

        $this->actingAs($user)->post('/admin/settings/advanced', [
            'analytics_ga4_id' => '',
            'analytics_plausible_domain' => '',
            'analytics_clarity_id' => '',
            'search_enabled' => true,
            'search_provider' => 'local',
            'llm_txt_enabled' => false,
            'llm_txt_include_drafts' => false,
            'llm_txt_max_tokens' => 100000,
            'meta_robots' => 'index',
            'code_copy_button' => true,
            'code_line_numbers' => true,
            'web_cron_enabled' => true,
        ]);

        SystemConfig::clearCache();
        $config = SystemConfig::instance();

        $this->assertNull($config->last_web_cron_at);
    }

    public function test_middleware_skips_error_responses(): void
    {
        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => null,
        ]);

        $this->get('/nonexistent-page-that-should-404');

        SystemConfig::clearCache();
        $config = SystemConfig::instance();

        $this->assertNull($config->last_web_cron_at);
    }

    public function test_middleware_acquires_lock_to_prevent_concurrent_runs(): void
    {
        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => null,
        ]);

        $lock = Cache::lock('web_cron_running', 120);
        $lock->get();

        $this->get('/');

        SystemConfig::clearCache();
        $config = SystemConfig::instance();

        $this->assertNull($config->last_web_cron_at);

        $lock->release();
    }

    public function test_middleware_releases_lock_after_completion(): void
    {
        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => null,
        ]);

        $this->get('/');

        $lock = Cache::lock('web_cron_running', 120);
        $acquired = $lock->get();

        $this->assertTrue($acquired, 'Lock should be available after middleware completes');

        $lock->release();
    }

    public function test_web_cron_settings_displayed_on_advanced_page(): void
    {
        $this->createConfig([
            'web_cron_enabled' => true,
            'last_web_cron_at' => now()->subMinutes(5),
        ]);

        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/admin/settings/advanced');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/settings/Advanced')
            ->has('webCron')
            ->has('serverCheck')
            ->where('webCron.web_cron_enabled', true)
        );
    }

    public function test_web_cron_can_be_toggled_via_settings(): void
    {
        $this->createConfig([
            'web_cron_enabled' => false,
            'last_web_cron_at' => null,
        ]);

        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->put('/admin/settings/advanced', [
            'analytics_ga4_id' => '',
            'analytics_plausible_domain' => '',
            'analytics_clarity_id' => '',
            'search_enabled' => true,
            'search_provider' => 'local',
            'llm_txt_enabled' => false,
            'llm_txt_include_drafts' => false,
            'llm_txt_max_tokens' => 100000,
            'meta_robots' => 'index',
            'code_copy_button' => true,
            'code_line_numbers' => true,
            'web_cron_enabled' => true,
        ]);

        $response->assertRedirect();

        SystemConfig::clearCache();
        $config = SystemConfig::instance();

        $this->assertTrue($config->web_cron_enabled);
    }

    public function test_server_compatibility_data_is_passed_to_frontend(): void
    {
        $this->createConfig([
            'web_cron_enabled' => false,
        ]);

        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/admin/settings/advanced');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('serverCheck.proc_open.available')
            ->has('serverCheck.max_execution_time')
            ->has('serverCheck.php_version')
            ->has('serverCheck.pending_jobs')
            ->has('serverCheck.failed_jobs')
            ->has('serverCheck.queue_driver')
        );
    }
}
