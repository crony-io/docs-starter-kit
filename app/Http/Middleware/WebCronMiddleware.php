<?php

namespace App\Http\Middleware;

use App\Models\SystemConfig;
use App\Services\WebCronService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebCronMiddleware
{
    public function __construct(
        private WebCronService $webCron
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Run AFTER response is prepared (terminate middleware)
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Only trigger on successful GET requests (page loads)
        if ($request->method() !== 'GET' || $response->getStatusCode() >= 400) {
            return;
        }

        // Skip AJAX/Inertia partial requests
        if ($request->header('X-Inertia-Partial-Data')) {
            return;
        }

        $config = SystemConfig::instance();

        // Feature toggle check
        if (! $config->web_cron_enabled) {
            return;
        }
        // Time-based throttle: max once per 60 seconds
        $lastRun = $config->last_web_cron_at;
        if ($lastRun && $lastRun->diffInSeconds(now()) < 60) {
            return;
        }

        // Atomic lock to prevent concurrent schedule:run
        $lock = Cache::lock('web_cron_running', 120);

        if (! $lock->get()) {
            return;
        }

        try {
            // Update timestamp BEFORE running
            $config->update(['last_web_cron_at' => now()]);
            SystemConfig::clearCache();
            // Run the scheduler (handles ALL scheduled tasks)
            $this->webCron->runScheduler();

        } catch (\Exception $e) {
            Log::error('Web-cron: Trigger failed', ['error' => $e->getMessage()]);
        } finally {
            $lock->release();
        }
    }
}
