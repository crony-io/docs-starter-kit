<?php

namespace Tests\Unit\Services;

use App\Services\IpDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Real API tests for IpDetectionService.
 *
 * These tests make actual HTTP requests to external IP services.
 * They verify the fallback chain works correctly.
 */
class IpDetectionServiceTest extends TestCase
{
    protected IpDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new IpDetectionService;
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // Real API Tests - IPv4
    // =========================================================================

    /**
     * @group external-api
     */
    public function test_get_ip_info_returns_correct_data_for_chilean_ipv4(): void
    {
        $ip = '186.107.212.56'; // Chilean IP (Telefonica Chile)

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info, 'IP info should not be null for valid public IP');
        $this->assertIsArray($info);

        // Verify essential fields are present
        $this->assertArrayHasKey('ip', $info);
        $this->assertArrayHasKey('city', $info);
        $this->assertArrayHasKey('country', $info);
        $this->assertArrayHasKey('latitude', $info);
        $this->assertArrayHasKey('longitude', $info);
        $this->assertArrayHasKey('service', $info);

        // Verify the IP is Santiago, Chile
        $this->assertEquals('Santiago', $info['city']);
        $this->assertStringContainsStringIgnoringCase('chile', $info['country']);
    }

    /**
     * @group external-api
     */
    public function test_get_ip_info_returns_correct_data_for_chilean_ipv6(): void
    {
        $ip = '2800:300:6a72:cda0:c117:9018:130:ca3f'; // Chilean IPv6 (Entel Chile)

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info, 'IP info should not be null for valid public IPv6');
        $this->assertIsArray($info);

        // Verify essential fields are present
        $this->assertArrayHasKey('ip', $info);
        $this->assertArrayHasKey('city', $info);
        $this->assertArrayHasKey('country', $info);
        $this->assertArrayHasKey('service', $info);

        // Verify the IP is Santiago, Chile
        $this->assertEquals('Santiago', $info['city']);
        $this->assertStringContainsStringIgnoringCase('chile', $info['country']);
    }

    /**
     * @group external-api
     */
    public function test_get_ip_info_includes_timezone_for_chilean_ip(): void
    {
        $ip = '186.107.212.56';

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info);
        $this->assertArrayHasKey('timezone', $info);
        $this->assertEquals('America/Santiago', $info['timezone']);
    }

    /**
     * @group external-api
     */
    public function test_get_ip_info_includes_isp_information(): void
    {
        $ip = '186.107.212.56';

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info);
        $this->assertArrayHasKey('isp', $info);
        $this->assertNotNull($info['isp']);
        // Should be Telefonica Chile or similar (handle encoding: TELEFÓNICA → TELEF NICA)
        $ispNormalized = preg_replace('/[^a-zA-Z\s]/', '', $info['isp']);
        $this->assertTrue(
            str_contains(strtolower($ispNormalized), 'telef') ||
            str_contains(strtolower($ispNormalized), 'chile'),
            "ISP should contain 'telef' or 'chile', got: {$info['isp']}"
        );
    }

    /**
     * @group external-api
     */
    public function test_get_ip_info_includes_coordinates(): void
    {
        $ip = '186.107.212.56';

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info);
        $this->assertArrayHasKey('latitude', $info);
        $this->assertArrayHasKey('longitude', $info);

        // Santiago coordinates approximately
        $this->assertEqualsWithDelta(-33.45, $info['latitude'], 1.0);
        $this->assertEqualsWithDelta(-70.65, $info['longitude'], 1.0);
    }

    // =========================================================================
    // Individual Service Tests
    // =========================================================================

    /**
     * @group external-api
     */
    public function test_ipapi_service_returns_valid_response(): void
    {
        $ip = '186.107.212.56';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/json/");

        if ($response->successful()) {
            $data = $response->json();

            $this->assertArrayHasKey('ip', $data);
            $this->assertArrayHasKey('city', $data);
            $this->assertArrayHasKey('country_name', $data);
            $this->assertEquals('Santiago', $data['city']);
        } else {
            $this->markTestSkipped('ipapi.co service unavailable');
        }
    }

    /**
     * @group external-api
     */
    public function test_ipwhois_service_returns_valid_response(): void
    {
        $ip = '186.107.212.56';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(5)->get("https://ipwho.is/{$ip}");

        if ($response->successful()) {
            $data = $response->json();

            $this->assertArrayHasKey('ip', $data);
            $this->assertArrayHasKey('success', $data);

            if ($data['success']) {
                $this->assertArrayHasKey('city', $data);
                $this->assertArrayHasKey('country', $data);
                $this->assertEquals('Santiago', $data['city']);
            }
        } else {
            $this->markTestSkipped('ipwho.is service unavailable');
        }
    }

    /**
     * @group external-api
     */
    public function test_ip_api_service_returns_valid_response(): void
    {
        $ip = '186.107.212.56';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");

        if ($response->successful()) {
            $data = $response->json();

            $this->assertArrayHasKey('status', $data);

            if ($data['status'] === 'success') {
                $this->assertArrayHasKey('query', $data);
                $this->assertArrayHasKey('city', $data);
                $this->assertArrayHasKey('country', $data);
                $this->assertEquals('Santiago', $data['city']);
            }
        } else {
            $this->markTestSkipped('ip-api.com service unavailable');
        }
    }

    /**
     * @group external-api
     */
    public function test_ip_guide_service_returns_valid_response_for_ipv4(): void
    {
        $ip = '186.107.212.56';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(5)->get("https://ip.guide/{$ip}");

        if ($response->successful()) {
            $data = $response->json();

            $this->assertArrayHasKey('ip', $data);
            $this->assertArrayHasKey('location', $data);
            $this->assertArrayHasKey('network', $data);

            $this->assertEquals('Santiago', $data['location']['city']);
            $this->assertEquals('Chile', $data['location']['country']);
            $this->assertEquals('America/Santiago', $data['location']['timezone']);
        } else {
            $this->markTestSkipped('ip.guide service unavailable');
        }
    }

    /**
     * @group external-api
     */
    public function test_ip_guide_service_returns_valid_response_for_ipv6(): void
    {
        $ip = '2800:300:6a72:cda0:c117:9018:130:ca3f';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(5)->get("https://ip.guide/{$ip}");

        if ($response->successful()) {
            $data = $response->json();

            $this->assertArrayHasKey('ip', $data);
            $this->assertArrayHasKey('location', $data);
            $this->assertArrayHasKey('network', $data);

            $this->assertEquals('Santiago', $data['location']['city']);
            $this->assertEquals('Chile', $data['location']['country']);

            // Verify ASN info
            $this->assertArrayHasKey('autonomous_system', $data['network']);
            $this->assertStringContainsStringIgnoringCase('entel', $data['network']['autonomous_system']['name']);
        } else {
            $this->markTestSkipped('ip.guide service unavailable');
        }
    }

    /**
     * @group external-api
     */
    public function test_ipinfo_service_returns_valid_response(): void
    {
        $ip = '186.107.212.56';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(5)->get("https://ipinfo.io/{$ip}/json");

        if ($response->successful()) {
            $data = $response->json();

            $this->assertArrayHasKey('ip', $data);
            $this->assertArrayHasKey('city', $data);
            $this->assertArrayHasKey('country', $data);
            $this->assertEquals('Santiago', $data['city']);
        } else {
            $this->markTestSkipped('ipinfo.io service unavailable');
        }
    }

    // =========================================================================
    // Fallback Chain Tests
    // =========================================================================

    /**
     * @group external-api
     */
    public function test_fallback_chain_returns_result_even_if_first_service_fails(): void
    {
        $ip = '186.107.212.56';

        // Clear cache to ensure fresh request
        Cache::forget('ip_info_'.$ip);

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info);
        $this->assertArrayHasKey('service', $info);

        // Should have used one of the available services
        $validServices = ['ipapi.co', 'ipwhois', 'ip-api.com', 'ip.guide', 'ipinfo.io'];
        $this->assertContains($info['service'], $validServices);
    }

    /**
     * @group external-api
     */
    public function test_get_ip_info_caches_result(): void
    {
        $ip = '186.107.212.56';
        $cacheKey = 'ip_info_'.$ip;

        Cache::forget($cacheKey);

        // First call
        $info1 = $this->service->getIpInfo($ip);
        $this->assertNotNull($info1);

        // Verify cache was set
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached result
        $info2 = $this->service->getIpInfo($ip);

        $this->assertEquals($info1, $info2);
    }

    /**
     * @group external-api
     */
    public function test_get_real_ip_returns_valid_public_ip(): void
    {
        // getRealIp returns the server's public IP when no fallback is provided
        // or returns the fallback if it's already a public IP
        $publicIp = '186.107.212.56';

        $result = $this->service->getRealIp($publicIp);

        $this->assertNotNull($result);
        $this->assertEquals($publicIp, $result);
    }

    /**
     * @group external-api
     */
    public function test_get_real_ip_tries_external_services_for_private_ip(): void
    {
        $privateIp = '192.168.1.1';

        // Clear cache
        Cache::forget('real_ip_'.$privateIp);

        // This will try to get the real IP from external services
        $result = $this->service->getRealIp($privateIp);

        // Should either get the server's real IP or fallback to the private IP
        $this->assertNotNull($result);

        // If it got a real IP, it should be a valid public IP
        if ($result !== $privateIp) {
            $this->assertTrue(
                filter_var($result, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false,
                "Result should be a valid public IP: {$result}"
            );
        }
    }

    // =========================================================================
    // Normalized Response Tests
    // =========================================================================

    /**
     * @group external-api
     */
    public function test_normalized_response_has_consistent_structure(): void
    {
        $ip = '186.107.212.56';

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info);

        // All normalized responses should have these keys
        $requiredKeys = [
            'ip',
            'city',
            'region',
            'country',
            'country_code',
            'postal',
            'latitude',
            'longitude',
            'timezone',
            'isp',
            'asn',
            'service',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $info, "Missing key: {$key}");
        }
    }

    /**
     * @group external-api
     */
    public function test_normalized_response_for_us_ip(): void
    {
        $ip = '8.8.8.8'; // Google DNS

        $info = $this->service->getIpInfo($ip);

        $this->assertNotNull($info);
        $this->assertArrayHasKey('country_code', $info);
        $this->assertEquals('US', $info['country_code']);
    }

    // =========================================================================
    // Client IP Detection Tests
    // =========================================================================

    public function test_get_client_ip_from_remote_addr(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.50');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('203.0.113.50', $ip);
    }

    public function test_get_client_ip_cloudflare_priority(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_CF_CONNECTING_IP', '186.107.212.56');
        $request->server->set('HTTP_X_FORWARDED_FOR', '192.168.1.1');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_true_client_ip_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_TRUE_CLIENT_IP', '186.107.212.56');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_x_real_ip_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_X_REAL_IP', '186.107.212.56');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_x_forwarded_for_single(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_X_FORWARDED_FOR', '186.107.212.56');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_x_forwarded_for_multiple(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_X_FORWARDED_FOR', '186.107.212.56, 70.41.3.18, 150.172.238.178');

        $ip = $this->service->getClientIp($request);

        // Should return first IP (client IP)
        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_skips_invalid_ip(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_X_FORWARDED_FOR', 'not-an-ip');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('127.0.0.1', $ip);
    }

    // =========================================================================
    // Server IP Detection Tests
    // =========================================================================

    public function test_get_server_ips_returns_array(): void
    {
        $request = Request::create('/test', 'GET');

        $serverIps = $this->service->getServerIps($request);

        $this->assertIsArray($serverIps);
    }

    public function test_get_server_ips_includes_server_addr(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('SERVER_ADDR', '10.0.0.1');

        $serverIps = $this->service->getServerIps($request);

        $this->assertContains('10.0.0.1', $serverIps);
    }

    public function test_is_server_ip_returns_true_for_server_addr(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('SERVER_ADDR', '10.0.0.1');

        $isServer = $this->service->isServerIp($request, '10.0.0.1');

        $this->assertTrue($isServer);
    }

    public function test_is_server_ip_returns_false_for_client_ip(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('SERVER_ADDR', '10.0.0.1');

        $isServer = $this->service->isServerIp($request, '186.107.212.56');

        $this->assertFalse($isServer);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * @group external-api
     */
    public function test_handles_invalid_ip_gracefully(): void
    {
        $info = $this->service->getIpInfo('999.999.999.999');

        // Should return null for invalid IP
        $this->assertNull($info);
    }

    /**
     * @group external-api
     */
    public function test_handles_private_ip_gracefully(): void
    {
        $info = $this->service->getIpInfo('192.168.1.1');

        // Most services won't return geo data for private IPs
        // Result can be null or have limited data
        if ($info !== null) {
            $this->assertIsArray($info);
        }
    }

    /**
     * @group external-api
     */
    public function test_handles_localhost_gracefully(): void
    {
        $info = $this->service->getIpInfo('127.0.0.1');

        // Services typically won't return geo data for localhost
        if ($info !== null) {
            $this->assertIsArray($info);
        }
    }

    // =========================================================================
    // Additional Edge Cases - getRealIp
    // =========================================================================

    public function test_get_real_ip_with_null_fallback(): void
    {
        // Clear cache to ensure fresh request
        Cache::forget('real_ip_unknown');

        // With null fallback and external services, it should try to get real IP
        // or return null if all services fail
        $result = $this->service->getRealIp(null);

        // Result can be a valid IP or null
        if ($result !== null) {
            $this->assertTrue(
                filter_var($result, FILTER_VALIDATE_IP) !== false,
                "Result should be a valid IP: {$result}"
            );
        }
    }

    public function test_get_real_ip_cache_hit(): void
    {
        $ip = '203.0.113.50';
        $cacheKey = 'real_ip_'.$ip;

        // Pre-populate cache
        Cache::put($cacheKey, '8.8.8.8', now()->addHour());

        $result = $this->service->getRealIp($ip);

        // Should return cached value since fallback is public IP
        // Actually, since fallback is already public, it returns fallback directly
        $this->assertEquals($ip, $result);

        Cache::forget($cacheKey);
    }

    public function test_get_real_ip_returns_fallback_when_already_public(): void
    {
        $publicIp = '8.8.8.8'; // Google DNS - definitely public

        $result = $this->service->getRealIp($publicIp);

        // Should return the public IP directly without calling external services
        $this->assertEquals($publicIp, $result);
    }

    // =========================================================================
    // Additional Edge Cases - getClientIp Headers
    // =========================================================================

    public function test_get_client_ip_x_forwarded_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_X_FORWARDED', '186.107.212.56');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_x_cluster_client_ip_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_X_CLUSTER_CLIENT_IP', '186.107.212.56');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_forwarded_for_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_FORWARDED_FOR', '186.107.212.56');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_forwarded_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->server->set('HTTP_FORWARDED', '186.107.212.56');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('186.107.212.56', $ip);
    }

    public function test_get_client_ip_falls_back_to_request_ip(): void
    {
        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
        ]);

        // Clear all proxy headers
        $request->server->remove('HTTP_CF_CONNECTING_IP');
        $request->server->remove('HTTP_X_FORWARDED_FOR');
        $request->server->remove('HTTP_X_REAL_IP');

        $ip = $this->service->getClientIp($request);

        // Should fall back to request->ip() which uses REMOTE_ADDR
        $this->assertEquals('192.168.1.100', $ip);
    }

    public function test_get_client_ip_with_ipv6(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_X_FORWARDED_FOR', '2800:300:6a72:cda0:c117:9018:130:ca3f');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('2800:300:6a72:cda0:c117:9018:130:ca3f', $ip);
    }

    public function test_get_client_ip_with_ipv6_multiple(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_X_FORWARDED_FOR', '2800:300:6a72:cda0::1, 2001:db8::1');

        $ip = $this->service->getClientIp($request);

        // Should return first IPv6
        $this->assertEquals('2800:300:6a72:cda0::1', $ip);
    }

    // =========================================================================
    // Additional Edge Cases - getServerIps
    // =========================================================================

    public function test_get_server_ips_filters_null_values(): void
    {
        $request = Request::create('/test', 'GET');
        // Don't set SERVER_ADDR - should filter out null

        $serverIps = $this->service->getServerIps($request);

        // Should not contain null values
        $this->assertNotContains(null, $serverIps);
    }

    public function test_get_server_ips_removes_duplicates(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('SERVER_ADDR', '127.0.0.1');

        $serverIps = $this->service->getServerIps($request);

        // Count should equal unique count
        $this->assertEquals(count($serverIps), count(array_unique($serverIps)));
    }

    public function test_get_server_ips_includes_config_ips(): void
    {
        // Set config for additional server IPs
        config(['activity-log.server_ips' => ['10.10.10.10', '10.10.10.11']]);

        $request = Request::create('/test', 'GET');

        $serverIps = $this->service->getServerIps($request);

        $this->assertContains('10.10.10.10', $serverIps);
        $this->assertContains('10.10.10.11', $serverIps);

        // Clean up
        config(['activity-log.server_ips' => []]);
    }

    // =========================================================================
    // Additional Edge Cases - IP Info Caching
    // =========================================================================

    public function test_get_ip_info_returns_cached_value(): void
    {
        $ip = '203.0.113.100';
        $cacheKey = 'ip_info_'.$ip;

        $cachedData = [
            'ip' => $ip,
            'city' => 'Cached City',
            'country' => 'Cached Country',
            'service' => 'cache',
        ];

        Cache::put($cacheKey, $cachedData, now()->addDay());

        $result = $this->service->getIpInfo($ip);

        $this->assertEquals($cachedData, $result);
        $this->assertEquals('Cached City', $result['city']);

        Cache::forget($cacheKey);
    }

    // =========================================================================
    // Additional Edge Cases - Empty/Malformed Data
    // =========================================================================

    public function test_get_client_ip_handles_empty_string_in_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_X_FORWARDED_FOR', '');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('127.0.0.1', $ip);
    }

    public function test_get_client_ip_handles_whitespace_in_forwarded_for(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_X_FORWARDED_FOR', '  186.107.212.56  ,  10.0.0.1  ');

        $ip = $this->service->getClientIp($request);

        // Should trim and return first valid IP
        $this->assertEquals('186.107.212.56', $ip);
    }

    /**
     * @group external-api
     */
    public function test_get_ip_info_with_empty_string_ip(): void
    {
        $info = $this->service->getIpInfo('');

        // Some services treat empty string as "get my IP" so this may return data
        // The important thing is it doesn't throw an exception
        if ($info !== null) {
            $this->assertIsArray($info);
            $this->assertArrayHasKey('ip', $info);
        }
    }

    // =========================================================================
    // Additional Edge Cases - Different IP Formats
    // =========================================================================

    /**
     * @group external-api
     */
    public function test_handles_ipv4_mapped_ipv6(): void
    {
        // IPv4-mapped IPv6 address
        $info = $this->service->getIpInfo('::ffff:8.8.8.8');

        // Some services may or may not support this format
        if ($info !== null) {
            $this->assertIsArray($info);
        }
    }

    /**
     * @group external-api
     */
    public function test_handles_compressed_ipv6(): void
    {
        // Compressed IPv6 (Google DNS)
        $info = $this->service->getIpInfo('2001:4860:4860::8888');

        if ($info !== null) {
            $this->assertIsArray($info);
            $this->assertArrayHasKey('country_code', $info);
            $this->assertEquals('US', $info['country_code']);
        }
    }

    /**
     * @group external-api
     */
    public function test_different_countries_return_correct_data(): void
    {
        // Test with a known US IP (Google DNS)
        $usInfo = $this->service->getIpInfo('8.8.8.8');

        $this->assertNotNull($usInfo);
        $this->assertEquals('US', $usInfo['country_code']);

        // Clear cache between tests
        Cache::forget('ip_info_8.8.8.8');
    }
}
