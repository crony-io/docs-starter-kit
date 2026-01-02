<?php

namespace App\Http\Middleware;

use App\Models\SystemConfig;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the application setup wizard is completed before allowing access.
 *
 * This middleware serves two purposes:
 * 1. Global (web middleware): Redirects ALL requests to /setup if setup is incomplete
 * 2. Route middleware alias 'setup.complete': Can be used on specific route groups
 *
 * Setup is considered complete when:
 * - SystemConfig::isSetupCompleted() returns true AND
 * - At least one user exists in the database
 */
class SetupMiddleware
{
    protected array $allowedRoutes = [
        'setup',
        'setup.*',
    ];

    protected array $allowedPrefixes = [
        'setup',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isSetupComplete()) {
            return $next($request);
        }

        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        if ($this->isAssetOrHealthRoute($request)) {
            return $next($request);
        }

        return redirect()->route('setup.index');
    }

    public static function isSetupComplete(): bool
    {
        return SystemConfig::isSetupCompleted() && User::count() > 0;
    }

    protected function isAllowedRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName) {
            foreach ($this->allowedRoutes as $pattern) {
                if ($routeName === $pattern || fnmatch($pattern, $routeName)) {
                    return true;
                }
            }
        }

        $path = trim($request->path(), '/');
        foreach ($this->allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function isAssetOrHealthRoute(Request $request): bool
    {
        $path = $request->path();

        return $request->is('up')
            || $request->is('_debugbar/*')
            || $request->is('__clockwork/*')
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'storage/')
            || str_ends_with($path, '.js')
            || str_ends_with($path, '.css')
            || str_ends_with($path, '.ico')
            || str_ends_with($path, '.png')
            || str_ends_with($path, '.jpg')
            || str_ends_with($path, '.svg');
    }
}
