<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class ActivityLogService
{
    public function __construct(
        protected IpDetectionService $ipDetectionService
    ) {}

    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        '_token',
        'git_access_token',
        'git_webhook_secret',
    ];

    private array $sensitiveHeaders = [
        'set-cookie',
        'authorization',
        'x-csrf-token',
        'x-xsrf-token',
        'cookie',
    ];

    /**
     * Log a user activity.
     */
    public function logActivity(
        Request $request,
        ?BaseResponse $response = null,
        ?int $executionTime = null,
        ?string $description = null,
        array $metadata = []
    ): void {
        try {
            $startTime = microtime(true);

            $logData = $this->prepareLogData($request, $response, $executionTime, $description, $metadata);

            ActivityLog::create($logData);

        } catch (\Exception $e) {
            // If logging fails, it should not affect the main application
            Log::error('Error logging activity', [
                'error' => $e->getMessage(),
                'url' => $request->url(),
                'user_id' => $request->user()?->id,
            ]);
        }
    }

    /**
     * Prepare log data from request and response.
     */
    private function prepareLogData(
        Request $request,
        ?BaseResponse $response,
        ?int $executionTime,
        ?string $description,
        array $metadata
    ): array {
        $user = $request->user();
        $route = $request->route();

        $action = $this->determineAction($request);
        $requestData = $this->sanitizeRequestData($request);
        $responseData = $this->extractResponseData($response);

        if (! $description) {
            $description = $this->generateDescription($request, $action);
        }

        // Get client IP (supports Cloudflare and proxies)
        $clientIp = $this->ipDetectionService->getClientIp($request);

        // Get real public IP and IP info (only for non-private, non-server IPs)
        $realIp = null;
        $ipInfo = null;

        $isServerIp = $this->ipDetectionService->isServerIp($request, $clientIp);

        if (! $this->isPrivateIp($clientIp) && ! $isServerIp) {
            try {
                $realIp = $this->ipDetectionService->getRealIp($clientIp);
                $lookupIp = ($realIp && $realIp !== $clientIp) ? $realIp : $clientIp;
                $ipInfo = $this->ipDetectionService->getIpInfo($lookupIp);
            } catch (\Exception $e) {
                Log::debug('Failed to get real IP info', ['error' => $e->getMessage()]);
            }
        }

        return [
            'user_id' => $user?->id,
            'action' => $action,
            'route_name' => $route?->getName(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $clientIp,
            'real_ip' => $realIp,
            'user_agent' => $request->userAgent(),
            'ip_info' => $ipInfo,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'status_code' => $response?->getStatusCode(),
            'execution_time' => $executionTime,
            'controller' => $this->getControllerName($route),
            'controller_action' => $this->getControllerAction($route),
            'description' => $description,
            'metadata' => array_merge($metadata, [
                'session_id' => $request->session()?->getId(),
                'referer' => $request->header('referer'),
                'content_type' => $request->header('content-type'),
                'accept' => $request->header('accept'),
                'cloudflare' => $request->header('CF-Connecting-IP') ? true : false,
            ]),
        ];
    }

    /**
     * Check if IP is private/local.
     */
    private function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Determine the action based on HTTP method and route.
     */
    private function determineAction(Request $request): string
    {
        $method = $request->method();
        $route = $request->route();

        $actionMap = [
            'GET' => 'query',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'partial_update',
            'DELETE' => 'delete',
        ];

        $baseAction = $actionMap[$method] ?? 'unknown_action';

        if ($route) {
            $uri = $route->uri();

            if (str_contains($uri, 'login')) {
                return 'login';
            }
            if (str_contains($uri, 'logout')) {
                return 'logout';
            }
            if (str_contains($uri, 'password/reset')) {
                return 'password_reset';
            }
            if (str_contains($uri, 'export')) {
                return 'data_export';
            }
            if (str_contains($uri, 'settings')) {
                return $baseAction.'_settings';
            }
        }

        return $baseAction;
    }

    /**
     * Sanitize request data by removing sensitive fields.
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();

        foreach ($this->sensitiveFields as $field) {
            unset($data[$field]);
        }
        if ($request->hasFile('*')) {
            $files = $request->allFiles();
            foreach ($files as $key => $file) {
                if (is_array($file)) {
                    $data[$key] = array_map(fn ($f) => $f->getClientOriginalName(), $file);
                } else {
                    $data[$key] = $file->getClientOriginalName();
                }
            }
        }

        return $data;
    }

    /**
     * Extract relevant data from response.
     */
    private function extractResponseData(?BaseResponse $response): ?array
    {
        if (! $response) {
            return null;
        }

        $headers = collect($response->headers->all())
            ->except($this->sensitiveHeaders)
            ->toArray();

        $data = [
            'status_code' => $response->getStatusCode(),
            'headers' => $headers,
        ];

        if (method_exists($response, 'getContent')) {
            try {
                $content = $response->getContent();
                if ($response->getStatusCode() >= 400 || strlen($content) < 1000) {
                    $data['content'] = $content;
                } else {
                    $data['content_length'] = strlen($content);
                }
            } catch (\Exception $e) {
                // StreamedResponse no tiene contenido accesible
                $data['content_type'] = 'streamed';
            }
        }

        return $data;
    }

    /**
     * Generate automatic description for the action.
     */
    private function generateDescription(Request $request, string $action): string
    {
        $user = $request->user();
        $userName = $user ? $user->name : 'Anonymous user';
        $method = $request->method();
        $url = $request->url();

        return "{$userName} performed a {$action} via {$method} at {$url}";
    }

    /**
     * Get controller name from route.
     */
    private function getControllerName($route): ?string
    {
        if (! $route || ! $route->getController()) {
            return null;
        }

        $controller = $route->getController();

        return class_basename($controller);
    }

    /**
     * Get controller action from route.
     */
    private function getControllerAction($route): ?string
    {
        if (! $route || ! $route->getActionMethod()) {
            return null;
        }

        return $route->getActionMethod();
    }

    /**
     * Get activity logs with optional filters.
     */
    public function getLogs(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = ActivityLog::query();

        if (! empty($filters['id'])) {
            $query->where('id', $filters['id']);
        }

        if (! empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (! empty($filters['action'])) {
            $query->forAction($filters['action']);
        }

        if (! empty($filters['route_name'])) {
            $query->forRoute($filters['route_name']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->inDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['successful']) && $filters['successful'] && ! isset($filters['with_errors'])) {
            $query->successful();
        }

        if (isset($filters['with_errors']) && $filters['with_errors'] && ! isset($filters['successful'])) {
            $query->withErrors();
        }

        if (isset($filters['redirects']) && $filters['redirects']) {
            $query->redirects();
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Clean old logs (older than specified days).
     */
    public function cleanOldLogs(int $days = 90): int
    {
        $cutoffDate = now()->subDays($days);

        return ActivityLog::where('created_at', '<', $cutoffDate)->delete();
    }
}
