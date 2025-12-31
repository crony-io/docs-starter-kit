<?php

namespace App\Services;

use App\Events\GitSyncCompleted;
use App\Events\GitSyncFailed;
use App\Models\GitSync;
use App\Models\Page;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GitSyncService
{
    public function __construct(
        private MarkdownParser $parser,
        private ContentImporter $importer
    ) {}

    public function sync(): GitSync
    {
        $config = SystemConfig::instance();

        if ($config->content_mode !== 'git') {
            throw new \Exception('Git sync is only available when content mode is set to git');
        }

        if (! $config->git_repository_url) {
            throw new \Exception('Git repository not configured');
        }

        $client = $this->createClient();

        // Get latest commit
        $latestCommit = $client->getLatestCommit();

        if (! $latestCommit) {
            throw new \Exception('Could not fetch latest commit');
        }

        // Check if already synced
        if ($this->isAlreadySynced($latestCommit['sha'])) {
            return GitSync::where('commit_hash', $latestCommit['sha'])->first();
        }

        // Create sync record
        $sync = GitSync::create([
            'commit_hash' => $latestCommit['sha'],
            'commit_message' => $latestCommit['message'],
            'commit_author' => $latestCommit['author'],
            'commit_date' => $latestCommit['date'],
            'sync_status' => 'in_progress',
        ]);

        try {
            DB::transaction(function () use ($client, $latestCommit, $sync) {
                // Get all markdown files from docs directory
                $tree = $client->getDirectoryTree('docs');
                $markdownFiles = collect($tree)
                    ->filter(fn ($item) => str_ends_with($item['path'], '.md'))
                    ->pluck('path')
                    ->toArray();

                $processedPaths = [];

                // Process each markdown file
                foreach ($markdownFiles as $path) {
                    try {
                        $content = $client->getFileContent($path);

                        if ($content) {
                            $parsed = $this->parser->parse($content, $path);
                            $this->importer->import($parsed, $latestCommit);
                            $processedPaths[] = $path;
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to process file {$path}: ".$e->getMessage());
                    }
                }

                // Delete pages that no longer exist in Git
                $deleted = $this->importer->deleteRemovedPages($processedPaths);

                // Update sync record
                $sync->update([
                    'sync_status' => 'success',
                    'files_changed' => count($processedPaths),
                    'sync_details' => [
                        'processed_files' => count($processedPaths),
                        'deleted' => $deleted,
                    ],
                ]);

                // Update config
                SystemConfig::instance()->update([
                    'last_synced_at' => now(),
                ]);
            });

            // Trigger events
            event(new GitSyncCompleted($sync));

            return $sync;

        } catch (\Exception $e) {
            $sync->update([
                'sync_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            event(new GitSyncFailed($sync, $e));

            throw $e;
        }
    }

    public function rollback(GitSync $sync): void
    {
        if (! $sync->isSuccess()) {
            throw new \Exception('Can only rollback to successful syncs');
        }

        DB::transaction(function () use ($sync) {
            // Delete all pages synced after this point
            Page::where('source', 'git')
                ->where('updated_at_git', '>', $sync->commit_date)
                ->delete();

            // Restore pages from this sync
            // This would require storing page snapshots or recreating from Git history
            // For now, we'll just trigger a fresh sync from this commit
        });
    }

    public function testConnection(?string $repositoryUrl = null, ?string $branch = null, ?string $token = null): bool
    {
        try {
            $client = $this->createClient(
                repositoryUrl: $repositoryUrl,
                branch: $branch,
                token: $token
            );

            return $client->testConnection();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function createClient(?string $repositoryUrl = null, ?string $branch = null, ?string $token = null): GitHubApiClient
    {
        $config = SystemConfig::instance();

        $finalRepositoryUrl = $repositoryUrl ?? $config->git_repository_url;
        $finalBranch = $branch ?? $config->git_branch;
        $finalToken = $token ?? $config->git_access_token;

        if (! $finalRepositoryUrl) {
            throw new \InvalidArgumentException('Git repository not configured');
        }

        return new GitHubApiClient(
            repository: $this->extractRepoFromUrl($finalRepositoryUrl),
            branch: $finalBranch ?: 'main',
            token: $finalToken
        );
    }

    private function extractRepoFromUrl(string $url): string
    {
        // Convert https://github.com/owner/repo to owner/repo
        return preg_replace('#^https?://github\.com/(.+?)(?:\.git)?$#', '$1', $url);
    }

    private function isAlreadySynced(string $commitHash): bool
    {
        return GitSync::where('commit_hash', $commitHash)
            ->where('sync_status', 'success')
            ->exists();
    }
}
