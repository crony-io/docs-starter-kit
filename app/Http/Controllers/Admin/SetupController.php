<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        // Security: Redirect if already setup AND users exist
        if (SystemConfig::isSetupCompleted() && User::count() > 0) {
            return redirect()->route('login');
        }

        return Inertia::render('setup/Index', [
            'hasUsers' => User::count() > 0,
            'isSetupCompleted' => SystemConfig::isSetupCompleted(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Security: Prevent setup if already completed with users
        if (SystemConfig::isSetupCompleted() && User::count() > 0) {
            abort(403, 'Setup already completed.');
        }

        $needsUser = User::count() === 0;

        $rules = [
            'content_mode' => 'required|in:git,cms',
            'git_repository_url' => 'required_if:content_mode,git|nullable|url',
            'git_branch' => 'required_if:content_mode,git|nullable|string',
            'git_access_token' => 'nullable|string',
            'git_webhook_secret' => 'nullable|string',
            'git_sync_frequency' => 'nullable|integer|min:5',
        ];

        // Add user validation rules if no users exist
        if ($needsUser) {
            $rules['admin_name'] = 'required|string|max:255';
            $rules['admin_email'] = 'required|string|email|max:255|unique:users,email';
            $rules['admin_password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        // Create admin user if needed
        if ($needsUser) {
            $user = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            // Auto-login the new admin
            Auth::login($user);
        }

        // Update system config
        $config = SystemConfig::instance();
        $config->update([
            'content_mode' => $validated['content_mode'],
            'git_repository_url' => $validated['git_repository_url'] ?? null,
            'git_branch' => $validated['git_branch'] ?? 'main',
            'git_access_token' => $validated['git_access_token'] ?? null,
            'git_webhook_secret' => $validated['git_webhook_secret'] ?? null,
            'git_sync_frequency' => $validated['git_sync_frequency'] ?? 15,
            'setup_completed' => true,
        ]);

        // If Git mode, trigger initial sync
        if ($validated['content_mode'] === 'git') {
            \App\Jobs\SyncGitRepositoryJob::dispatch();
        }

        return redirect()->route('dashboard')
            ->with('success', 'Setup completed successfully!');
    }
}
