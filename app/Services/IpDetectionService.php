<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpDetectionService
{
    /**
     * IP detection services with fallbacks (in order of priority).
     */
    private array $ipServices = [
        'ipapi' => 'https://ipapi.co/json/',
        'ipwhois' => 'https://ipwho.is/',
        'ipify' => 'https://api.ipify.org?format=json',
        'ip-api' => 'http://ip-api.com/json/',
        'ipinfo' => 'https://ipinfo.io/json',
        'ip-guide' => 'https://ip.guide/',
    ];

    /**
     * IP info services with fallbacks (in order of priority).
     */
    private array $ipInfoServices = [
        'ipapi',
        'ipwhois',
        'ip-api',
        'ip-guide',
        'ipinfo',
    ];

    /**
     * Get the real public IP address with fallbacks.
     */
    public function getRealIp(?string $fallbackIp = null): ?string
    {
        if ($fallbackIp && filter_var($fallbackIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $fallbackIp;
        }

        // Try to get from cache first (cache for 1 hour per IP)
        $cacheKey = 'real_ip_'.($fallbackIp ?? 'unknown');

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        foreach ($this->ipServices as $serviceName => $serviceUrl) {
            try {
                /** @var \Illuminate\Http\Client\Response $response */
                $response = Http::timeout(3)->get($serviceUrl);

                if ($response->successful()) {
                    $data = $response->json();
                    $ip = $this->extractIpFromResponse($serviceName, $data);

                    if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        // Cache for 1 hour
                        Cache::put($cacheKey, $ip, now()->addHour());

                        return $ip;
                    }
                }
            } catch (\Exception $e) {
                Log::debug("IP detection service {$serviceName} failed", [
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        // All services failed, return fallback
        return $fallbackIp;
    }

    /**
     * Get detailed IP information including geolocation.
     */
    public function getIpInfo(string $ip): ?array
    {
        $cacheKey = 'ip_info_'.$ip;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        foreach ($this->ipInfoServices as $serviceName) {
            try {
                $info = $this->fetchIpInfo($serviceName, $ip);

                if ($info) {
                    Cache::put($cacheKey, $info, now()->addDay());

                    return $info;
                }
            } catch (\Exception $e) {
                Log::debug("{$serviceName} failed for IP info", ['error' => $e->getMessage()]);

                continue;
            }
        }

        return null;
    }

    /**
     * Fetch IP info from a specific service.
     */
    private function fetchIpInfo(string $serviceName, string $ip): ?array
    {
        /** @var \Illuminate\Http\Client\Response|null $response */
        $response = match ($serviceName) {
            'ipapi' => Http::timeout(5)->get("https://ipapi.co/{$ip}/json/"),
            'ipwhois' => Http::timeout(5)->get("https://ipwho.is/{$ip}"),
            'ip-api' => Http::timeout(5)->get("http://ip-api.com/json/{$ip}"),
            'ip-guide' => Http::timeout(5)->get("https://ip.guide/{$ip}"),
            'ipinfo' => Http::timeout(5)->get("https://ipinfo.io/{$ip}/json"),
            default => null,
        };

        if (! $response || ! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return $this->normalizeIpInfo($serviceName, $ip, $data);
    }

    /**
     * Normalize IP info response from different services to a common format.
     */
    private function normalizeIpInfo(string $serviceName, string $ip, array $data): ?array
    {
        return match ($serviceName) {
            'ipapi' => [
                'ip' => $data['ip'] ?? $ip,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country_name'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'postal' => $data['postal'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'isp' => $data['org'] ?? null,
                'asn' => $data['asn'] ?? null,
                'service' => 'ipapi.co',
            ],
            'ipwhois' => ($data['success'] ?? false) ? [
                'ip' => $data['ip'] ?? $ip,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'postal' => $data['postal'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'timezone' => $data['timezone']['id'] ?? null,
                'isp' => $data['connection']['isp'] ?? null,
                'asn' => $data['connection']['asn'] ?? null,
                'service' => 'ipwhois',
            ] : null,
            'ip-api' => (($data['status'] ?? '') === 'success') ? [
                'ip' => $data['query'] ?? $ip,
                'city' => $data['city'] ?? null,
                'region' => $data['regionName'] ?? null,
                'country' => $data['country'] ?? null,
                'country_code' => $data['countryCode'] ?? null,
                'postal' => $data['zip'] ?? null,
                'latitude' => $data['lat'] ?? null,
                'longitude' => $data['lon'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'isp' => $data['isp'] ?? null,
                'asn' => $data['as'] ?? null,
                'service' => 'ip-api.com',
            ] : null,
            'ip-guide' => isset($data['ip']) ? [
                'ip' => $data['ip'] ?? $ip,
                'city' => $data['location']['city'] ?? null,
                'region' => null,
                'country' => $data['location']['country'] ?? null,
                'country_code' => $data['network']['autonomous_system']['country'] ?? null,
                'postal' => null,
                'latitude' => $data['location']['latitude'] ?? null,
                'longitude' => $data['location']['longitude'] ?? null,
                'timezone' => $data['location']['timezone'] ?? null,
                'isp' => $data['network']['autonomous_system']['organization'] ?? null,
                'asn' => isset($data['network']['autonomous_system']['asn'])
                    ? 'AS'.$data['network']['autonomous_system']['asn']
                    : null,
                'service' => 'ip.guide',
            ] : null,
            'ipinfo' => isset($data['ip']) ? [
                'ip' => $data['ip'] ?? $ip,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country'] ?? null,
                'country_code' => $data['country'] ?? null,
                'postal' => $data['postal'] ?? null,
                'latitude' => isset($data['loc']) ? (float) explode(',', $data['loc'])[0] : null,
                'longitude' => isset($data['loc']) ? (float) explode(',', $data['loc'])[1] : null,
                'timezone' => $data['timezone'] ?? null,
                'isp' => $data['org'] ?? null,
                'asn' => null,
                'service' => 'ipinfo.io',
            ] : null,
            default => null,
        };
    }

    /**
     * Extract IP from different service response formats.
     */
    private function extractIpFromResponse(string $serviceName, array $data): ?string
    {
        return match ($serviceName) {
            'ipapi' => $data['ip'] ?? null,
            'ipwhois' => $data['ip'] ?? null,
            'ipify' => $data['ip'] ?? null,
            'ip-api' => $data['query'] ?? null,
            'ipinfo' => $data['ip'] ?? null,
            'ip-guide' => $data['ip'] ?? null,
            default => null,
        };
    }

    /**
     * Get all possible server IPs (for filtering out server-to-server requests).
     */
    public function getServerIps(\Illuminate\Http\Request $request): array
    {
        $hostname = gethostname();
        $hostIp = $hostname ? gethostbyname($hostname) : null;

        return collect([
            $request->server('SERVER_ADDR'),
            ($hostIp && $hostIp !== $hostname) ? $hostIp : null,
        ])
            ->merge(config('activity-log.server_ips', []))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Check if the given IP is a server IP.
     */
    public function isServerIp(\Illuminate\Http\Request $request, string $ip): bool
    {
        return in_array($ip, $this->getServerIps($request), true);
    }

    /**
     * Get client IP from request headers (supports Cloudflare and other proxies).
     */
    public function getClientIp(\Illuminate\Http\Request $request): string
    {
        // Priority order for detecting real IP behind proxies
        $headers = [
            'HTTP_CF_CONNECTING_IP',      // Cloudflare
            'HTTP_TRUE_CLIENT_IP',         // Cloudflare Enterprise
            'HTTP_X_REAL_IP',              // Nginx proxy
            'HTTP_X_FORWARDED_FOR',        // Standard proxy header
            'HTTP_X_FORWARDED',            // Alternative
            'HTTP_X_CLUSTER_CLIENT_IP',    // Rackspace LB
            'HTTP_FORWARDED_FOR',          // RFC 7239
            'HTTP_FORWARDED',              // RFC 7239
            'REMOTE_ADDR',                 // Direct connection
        ];

        foreach ($headers as $header) {
            $ip = $request->server($header);

            if ($ip) {
                // Handle comma-separated IPs (X-Forwarded-For can have multiple IPs)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }
}
