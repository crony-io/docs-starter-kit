<?php

namespace App\Http\Middleware;

use App\Models\SystemConfig;
use Closure;
use Illuminate\Http\Request;

class EnsureSetupComplete
{
    public function handle(Request $request, Closure $next)
    {
        if (! SystemConfig::isSetupComplete() && ! $request->is('admin/setup*')) {
            return redirect()->route('admin.setup');
        }

        return $next($request);
    }
}
