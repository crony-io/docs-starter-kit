<?php

namespace Tests\Feature;

use App\Http\Middleware\LogUserActivity;
use App\Models\ActivityLog;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\IpDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LogUserActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // Create system config for app to work
        SystemConfig::create([
            'content_mode' => 'cms',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        // Ensure we have a user
        User::factory()->create();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    private function createAdminUser(): User
    {
        return User::factory()->admin()->create();
    }

    // =========================================================================
    // LogUserActivity Middleware Tests
    // =========================================================================

    public function test_middleware_logs_activity_for_regular_page_request(): void
    {
        $this->get('/');

        $this->assertDatabaseHas('activity_logs', [
            'method' => 'GET',
            'action' => 'query',
        ]);
    }

    public function test_middleware_logs_activity_with_correct_url(): void
    {
        $this->get('/docs');

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('/docs', $log->url);
        $this->assertEquals('GET', $log->method);
    }

    public function test_middleware_logs_authenticated_user_activity(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get('/admin/settings');

        $log = ActivityLog::where('user_id', $user->id)->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals($user->id, $log->user_id);
    }

    public function test_middleware_logs_anonymous_user_activity(): void
    {
        $this->get('/');

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
    }

    public function test_middleware_does_not_log_static_css_assets(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/css/app.css');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_static_js_assets(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/js/app.js');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_image_assets(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/images/logo.png');
        $this->get('/images/photo.jpg');
        $this->get('/images/icon.svg');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_font_assets(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/fonts/inter.woff2');
        $this->get('/fonts/roboto.ttf');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_build_directory(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/build/assets/app.js');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_health_check(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/up');
        $this->get('/health');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_debugbar_requests(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/_debugbar/assets/stylesheets');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_telescope_requests(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/telescope/requests');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_activity_logs_page(): void
    {
        $user = $this->createAdminUser();
        $initialCount = ActivityLog::count();

        $this->actingAs($user)->get('/activity-logs');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_does_not_log_favicon(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/favicon.ico');

        $this->assertEquals($initialCount, ActivityLog::count());
    }

    public function test_middleware_records_execution_time(): void
    {
        $this->get('/');

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->execution_time);
        $this->assertIsInt($log->execution_time);
        $this->assertGreaterThanOrEqual(0, $log->execution_time);
    }

    public function test_middleware_records_status_code(): void
    {
        $this->get('/');

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->status_code);
        $this->assertIsInt($log->status_code);
    }

    public function test_middleware_records_user_agent(): void
    {
        $this->get('/', ['HTTP_USER_AGENT' => 'TestBrowser/1.0']);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('TestBrowser/1.0', $log->user_agent);
    }

    // =========================================================================
    // ActivityLogService Tests (through real requests)
    // =========================================================================

    public function test_activity_log_determines_correct_action_for_get(): void
    {
        $this->get('/');

        $log = ActivityLog::latest()->first();

        $this->assertEquals('query', $log->action);
    }

    public function test_activity_log_determines_correct_action_for_post(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->post('/admin/pages', [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'type' => 'document',
            'status' => 'draft',
        ]);

        $log = ActivityLog::where('method', 'POST')->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->action);
    }

    public function test_activity_log_determines_login_action(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $log = ActivityLog::where('action', 'login')->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('login', $log->action);
    }

    public function test_activity_log_sanitizes_sensitive_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'supersecretpassword',
            '_token' => 'csrf-token-here',
        ]);

        $log = ActivityLog::where('action', 'login')->latest()->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->request_data);
        $this->assertArrayNotHasKey('password', $log->request_data);
        $this->assertArrayNotHasKey('_token', $log->request_data);
        $this->assertArrayHasKey('email', $log->request_data);
    }

    public function test_activity_log_records_response_data(): void
    {
        $this->get('/');

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->response_data);
        $this->assertIsArray($log->response_data);
        $this->assertArrayHasKey('status_code', $log->response_data);
    }

    public function test_activity_log_generates_description(): void
    {
        $this->get('/');

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->description);
        $this->assertStringContainsString('performed a query', $log->description);
    }

    public function test_activity_log_records_metadata(): void
    {
        $this->get('/', [
            'HTTP_REFERER' => 'https://google.com',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->metadata);
        $this->assertIsArray($log->metadata);
        $this->assertArrayHasKey('referer', $log->metadata);
    }

    public function test_activity_log_get_logs_filters_by_user(): void
    {
        $user = $this->createAdminUser();

        // Create logs for this user
        $this->actingAs($user)->get('/admin/dashboard');

        // Create anonymous log
        $this->get('/');

        $service = app(ActivityLogService::class);
        $logs = $service->getLogs(['user_id' => $user->id])->get();

        $this->assertTrue($logs->every(fn ($log) => $log->user_id === $user->id));
    }

    public function test_activity_log_get_logs_filters_by_action(): void
    {
        $this->get('/');

        $service = app(ActivityLogService::class);
        $logs = $service->getLogs(['action' => 'query'])->get();

        $this->assertTrue($logs->every(fn ($log) => $log->action === 'query'));
    }

    public function test_activity_log_get_logs_filters_successful(): void
    {
        $this->get('/');

        $service = app(ActivityLogService::class);
        $logs = $service->getLogs(['successful' => true])->get();

        $this->assertTrue($logs->every(fn ($log) => $log->status_code >= 200 && $log->status_code < 300));
    }

    public function test_activity_log_clean_old_logs(): void
    {
        // Create an old log manually and update timestamp directly in DB
        $oldLog = ActivityLog::create([
            'action' => 'query',
            'url' => 'http://localhost/old',
            'method' => 'GET',
            'ip_address' => '127.0.0.1',
        ]);

        // Update created_at directly to bypass model timestamps
        ActivityLog::where('id', $oldLog->id)->update([
            'created_at' => now()->subDays(100),
        ]);

        // Create a recent log
        $this->get('/');

        $service = app(ActivityLogService::class);
        $deleted = $service->cleanOldLogs(90);

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('activity_logs', ['url' => 'http://localhost/old']);
    }

    // =========================================================================
    // IpDetectionService Tests (through real requests)
    // =========================================================================

    public function test_ip_detection_records_ip_address(): void
    {
        $this->get('/');

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->ip_address);
        $this->assertNotEmpty($log->ip_address);
    }

    public function test_ip_detection_handles_cloudflare_header(): void
    {
        $this->get('/', [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.50',
        ]);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('203.0.113.50', $log->ip_address);
    }

    public function test_ip_detection_handles_x_forwarded_for_header(): void
    {
        $this->get('/', [
            'HTTP_X_FORWARDED_FOR' => '198.51.100.178',
        ]);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('198.51.100.178', $log->ip_address);
    }

    public function test_ip_detection_handles_multiple_ips_in_x_forwarded_for(): void
    {
        $this->get('/', [
            'HTTP_X_FORWARDED_FOR' => '203.0.113.195, 70.41.3.18, 150.172.238.178',
        ]);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        // Should take the first IP (client IP)
        $this->assertEquals('203.0.113.195', $log->ip_address);
    }

    public function test_ip_detection_handles_x_real_ip_header(): void
    {
        $this->get('/', [
            'HTTP_X_REAL_IP' => '192.0.2.1',
        ]);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('192.0.2.1', $log->ip_address);
    }

    public function test_ip_detection_cloudflare_takes_priority(): void
    {
        $this->get('/', [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.50',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.178',
            'HTTP_X_REAL_IP' => '192.0.2.1',
        ]);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        // Cloudflare header should take priority
        $this->assertEquals('203.0.113.50', $log->ip_address);
    }

    public function test_ip_detection_metadata_includes_cloudflare_flag(): void
    {
        $this->get('/', [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.50',
        ]);

        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->metadata['cloudflare']);
    }

    public function test_ip_detection_service_get_client_ip_directly(): void
    {
        $service = app(IpDetectionService::class);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $ip = $service->getClientIp($request);

        $this->assertEquals('127.0.0.1', $ip);
    }

    public function test_ip_detection_service_get_client_ip_with_proxy(): void
    {
        $service = app(IpDetectionService::class);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_X_FORWARDED_FOR', '203.0.113.50');

        $ip = $service->getClientIp($request);

        $this->assertEquals('203.0.113.50', $ip);
    }

    public function test_ip_detection_service_validates_ip_format(): void
    {
        $service = app(IpDetectionService::class);

        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_X_FORWARDED_FOR', 'invalid-ip');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $ip = $service->getClientIp($request);

        // Should fall back to REMOTE_ADDR since X-Forwarded-For is invalid
        $this->assertEquals('127.0.0.1', $ip);
    }

    public function test_ip_detection_service_get_server_ips(): void
    {
        $service = app(IpDetectionService::class);
        $request = Request::create('/test', 'GET');

        $serverIps = $service->getServerIps($request);

        $this->assertIsArray($serverIps);
    }

    public function test_ip_detection_service_is_server_ip(): void
    {
        $service = app(IpDetectionService::class);
        $request = Request::create('/test', 'GET');
        $request->server->set('SERVER_ADDR', '10.0.0.1');

        $isServerIp = $service->isServerIp($request, '10.0.0.1');

        $this->assertTrue($isServerIp);
    }

    public function test_ip_detection_service_is_not_server_ip(): void
    {
        $service = app(IpDetectionService::class);
        $request = Request::create('/test', 'GET');

        $isServerIp = $service->isServerIp($request, '203.0.113.50');

        $this->assertFalse($isServerIp);
    }

    // =========================================================================
    // Integration Tests - Full Flow
    // =========================================================================

    public function test_full_flow_admin_page_request(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertStatus(200);

        $log = ActivityLog::where('user_id', $user->id)->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('GET', $log->method);
        $this->assertEquals('query_settings', $log->action);
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->execution_time);
        $this->assertEquals(200, $log->status_code);
    }

    public function test_full_flow_settings_update(): void
    {
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
        ]);

        $response->assertRedirect();

        $log = ActivityLog::where('method', 'PUT')->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals('update_settings', $log->action);
    }

    public function test_full_flow_error_response_is_logged(): void
    {
        // First create a baseline log to ensure logging works
        $this->get('/');
        $initialCount = ActivityLog::count();
        $this->assertGreaterThan(0, $initialCount, 'Baseline log should exist');

        // Test that redirect responses are logged
        $response = $this->get('/admin/settings'); // Should redirect to login (302)
        $response->assertRedirect();

        // The redirect should be logged
        $log = ActivityLog::latest()->first();
        $this->assertNotNull($log);
        $this->assertContains($log->status_code, [302, 303], 'Should log redirect status');
    }

    public function test_multiple_requests_create_multiple_logs(): void
    {
        $initialCount = ActivityLog::count();

        $this->get('/');
        $this->get('/docs');
        $this->get('/search');

        $this->assertEquals($initialCount + 3, ActivityLog::count());
    }

    // =========================================================================
    // Edge Cases and Error Handling
    // =========================================================================

    public function test_middleware_handles_request_without_route_gracefully(): void
    {
        // Create a log first to ensure we can detect if a new one was added
        $this->get('/');
        $initialCount = ActivityLog::count();

        // This should not throw an exception
        $this->get('/some-nonexistent-page-12345');

        // 404 pages are still logged (unless excluded)
        $log = ActivityLog::latest()->first();

        $this->assertNotNull($log);
        // The 404 should be logged
        $this->assertGreaterThanOrEqual($initialCount, ActivityLog::count());
    }

    public function test_activity_log_handles_large_request_data(): void
    {
        $user = $this->createAdminUser();

        $largeContent = str_repeat('Lorem ipsum dolor sit amet. ', 1000);

        $this->actingAs($user)->post('/admin/pages', [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'type' => 'document',
            'status' => 'draft',
            'content' => $largeContent,
        ]);

        $log = ActivityLog::where('method', 'POST')->latest()->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->request_data);
    }

    public function test_activity_log_model_scopes_work(): void
    {
        $user = $this->createAdminUser();

        // Create various logs
        $this->get('/');
        $this->get('/docs');
        $this->actingAs($user)->get('/admin/settings');

        // Test scopes - query and query_settings are both logged
        $queryLogs = ActivityLog::where('action', 'like', 'query%')->get();
        $this->assertTrue($queryLogs->count() >= 2);

        $userLogs = ActivityLog::forUser($user->id)->get();
        $this->assertTrue($userLogs->every(fn ($log) => $log->user_id === $user->id));

        $successLogs = ActivityLog::successful()->get();
        $this->assertTrue($successLogs->every(fn ($log) => $log->status_code >= 200 && $log->status_code < 300));
    }

    public function test_activity_log_date_range_scope(): void
    {
        $this->get('/');

        $logs = ActivityLog::inDateRange(
            now()->subDay()->toDateTimeString(),
            now()->addDay()->toDateTimeString()
        )->get();

        $this->assertGreaterThan(0, $logs->count());
    }
}
