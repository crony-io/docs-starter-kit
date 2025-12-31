<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGitRepositoryJob;
use App\Models\GitSync;
use App\Models\SystemConfig;
use App\Services\GitSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GitSyncController extends Controller
{
    public function __construct(
        private GitSyncService $syncService
    ) {}

    public function index(): Response
    {
        $config = SystemConfig::instance();

        return Inertia::render('admin/git-sync/Index', [
            'config' => [
                'content_mode' => $config->content_mode,
                'git_repository_url' => $config->git_repository_url,
                'git_branch' => $config->git_branch,
                'git_sync_frequency' => $config->git_sync_frequency,
                'last_synced_at' => $config->last_synced_at,
                'git_access_token_configured' => filled($config->git_access_token),
                'git_webhook_secret_configured' => filled($config->git_webhook_secret),
                'setup_completed' => $config->setup_completed,
                'webhook_url' => route('webhook.github'),
            ],
            'syncs' => GitSync::recent(20)
                ->get()
                ->map(fn ($sync) => [
                    'id' => $sync->id,
                    'commit_hash' => $sync->commit_hash,
                    'commit_message' => $sync->commit_message,
                    'commit_author' => $sync->commit_author,
                    'commit_date' => $sync->commit_date,
                    'status' => $sync->sync_status,
                    'files_changed' => $sync->files_changed,
                    'error_message' => $sync->error_message,
                    'sync_details' => $sync->sync_details,
                    'created_at' => $sync->created_at,
                    'updated_at' => $sync->updated_at,
                ]),
        ]);
    }

    public function manualSync()
    {
        try {
            $config = SystemConfig::instance();

            if ($config->content_mode !== 'git') {
                return back()->with('error', 'Git sync is only available when content mode is set to git.');
            }

            if (! $config->git_repository_url) {
                return back()->with('error', 'Git repository is not configured.');
            }

            SyncGitRepositoryJob::dispatch()->onQueue('high-priority');

            return back()->with('success', 'Git sync has been queued and will start shortly.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to queue sync: '.$e->getMessage());
        }
    }

    public function testConnection()
    {
        try {
            $validated = request()->validate([
                'git_repository_url' => 'nullable|url',
                'git_branch' => 'nullable|string',
                'git_access_token' => 'nullable|string',
            ]);

            $success = $this->syncService->testConnection(
                repositoryUrl: $validated['git_repository_url'] ?? null,
                branch: $validated['git_branch'] ?? null,
                token: $validated['git_access_token'] ?? null
            );

            if (request()->wantsJson()) {
                if ($success) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Successfully connected to repository',
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to repository',
                ], 422);
            }

            if ($success) {
                return back()->with('success', 'Successfully connected to repository.');
            }

            return back()->with('fail', 'Failed to connect to repository.');

        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return back()->with('fail', $e->getMessage());
        }
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'git_repository_url' => 'required|url',
            'git_branch' => 'required|string',
            'git_access_token' => 'nullable|string',
            'git_webhook_secret' => 'nullable|string',
            'git_sync_frequency' => 'required|integer|min:5|max:1440',
            'clear_git_access_token' => 'sometimes|boolean',
            'clear_git_webhook_secret' => 'sometimes|boolean',
        ]);

        $clearToken = (bool) ($validated['clear_git_access_token'] ?? false);
        $clearWebhookSecret = (bool) ($validated['clear_git_webhook_secret'] ?? false);

        unset($validated['clear_git_access_token'], $validated['clear_git_webhook_secret']);

        if ($clearToken) {
            $validated['git_access_token'] = null;
        } elseif (array_key_exists('git_access_token', $validated) && $validated['git_access_token'] === '') {
            unset($validated['git_access_token']);
        }

        if ($clearWebhookSecret) {
            $validated['git_webhook_secret'] = null;
        } elseif (array_key_exists('git_webhook_secret', $validated) && $validated['git_webhook_secret'] === '') {
            unset($validated['git_webhook_secret']);
        }

        $config = SystemConfig::instance();
        $config->update($validated);

        return back()->with('success', 'Git configuration updated successfully.');
    }

    public function rollback(GitSync $sync)
    {
        try {
            if (! $sync->isSuccess()) {
                return back()->with('error', 'Can only rollback to successful syncs.');
            }

            $this->syncService->rollback($sync);

            return back()->with('success', 'Successfully rolled back to commit: '.substr($sync->commit_hash, 0, 7));
        } catch (\Exception $e) {
            return back()->with('error', 'Rollback failed: '.$e->getMessage());
        }
    }
}
