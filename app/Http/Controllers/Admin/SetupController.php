<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\GitSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function __construct(
        private GitSyncService $syncService
    ) {}

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
            'site_name' => 'required|string|max:100',
            'site_tagline' => 'nullable|string|max:200',
            'show_footer' => 'required',
            'footer_text' => 'nullable|string|max:500',
            'meta_robots' => 'required|string|in:index,noindex',
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

        // Save site settings
        Setting::set('branding_site_name', $validated['site_name'], 'branding');
        Setting::set('branding_site_tagline', $validated['site_tagline'] ?? '', 'branding');
        Setting::set('layout_show_footer', filter_var($validated['show_footer'], FILTER_VALIDATE_BOOLEAN), 'layout');
        Setting::set('layout_footer_text', $validated['footer_text'] ?? '', 'layout');
        Setting::set('advanced_meta_robots', $validated['meta_robots'], 'advanced');

        return redirect()->route('dashboard')
            ->with('success', 'Setup completed successfully!');
    }

    public function testConnection(Request $request): RedirectResponse
    {
        // Security: Only allow during setup
        if (SystemConfig::isSetupCompleted() && User::count() > 0) {
            abort(403, 'Setup already completed.');
        }

        $validated = $request->validate([
            'git_repository_url' => 'required|url',
            'git_branch' => 'required|string',
            'git_access_token' => 'nullable|string',
        ]);

        try {
            $success = $this->syncService->testConnection(
                repositoryUrl: $validated['git_repository_url'],
                branch: $validated['git_branch'],
                token: $validated['git_access_token'] ?? null
            );

            if (! $success) {
                throw ValidationException::withMessages([
                    'git_repository_url' => 'The connection failed, please check your URL, branch, and/or access token.',
                ]);
            }

            return back()->with('success', 'Successfully connected to repository.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'git_repository_url' => 'Connection error: '.$e->getMessage(),
            ]);
        }
    }
}
