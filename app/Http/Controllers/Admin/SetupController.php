<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        // Redirect if already setup
        if (SystemConfig::isSetupCompleted()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('admin/setup/Index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content_mode' => 'required|in:git,cms',
            'git_repository_url' => 'required_if:content_mode,git|nullable|url',
            'git_branch' => 'required_if:content_mode,git|nullable|string',
            'git_access_token' => 'nullable|string',
            'git_webhook_secret' => 'nullable|string',
            'git_sync_frequency' => 'nullable|integer|min:5',
        ]);

        $config = SystemConfig::instance();
        $config->update([
            ...$validated,
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
