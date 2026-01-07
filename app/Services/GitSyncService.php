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
    private const RESERVED_FOLDERS = ['assets'];

    public function __construct(
        private MarkdownParser $parser,
        private ContentImporter $importer
    ) {}

    public function sync(bool $force = false): GitSync
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

        // Get last successful sync for differential sync
        $lastSuccessfulSync = GitSync::where('sync_status', 'success')
            ->latest()
            ->first();

        // Create sync record
        $sync = GitSync::create([
            'commit_hash' => $latestCommit['sha'],
            'commit_message' => $latestCommit['message'],
            'commit_author' => $latestCommit['author'],
            'commit_date' => $latestCommit['date'],
            'sync_status' => 'in_progress',
        ]);

        try {
            DB::transaction(function () use ($client, $latestCommit, $sync, $lastSuccessfulSync, $force) {
                $processedPaths = [];
                $deletedPaths = [];
                $isFullSync = $force || ! $lastSuccessfulSync;

                if ($isFullSync) {
                    // Full sync: process all files
                    $result = $this->performFullSync($client, $latestCommit);
                    $processedPaths = $result['processed'];
                    $deletedPaths = $result['deleted'];
                } else {
                    // Differential sync: only process changed files
                    $result = $this->performDifferentialSync(
                        $client,
                        $lastSuccessfulSync->commit_hash,
                        $latestCommit
                    );
                    $processedPaths = $result['processed'];
                    $deletedPaths = $result['deleted'];
                }

                // Update sync record
                $sync->update([
                    'sync_status' => 'success',
                    'files_changed' => count($processedPaths) + count($deletedPaths),
                    'sync_details' => [
                        'sync_type' => $isFullSync ? 'full' : 'differential',
                        'processed_files' => count($processedPaths),
                        'deleted_files' => count($deletedPaths),
                        'from_commit' => $lastSuccessfulSync?->commit_hash,
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

    private function performFullSync(GitHubApiClient $client, array $latestCommit): array
    {
        // Get all files from docs directory
        $tree = $client->getDirectoryTree('docs');

        // First, load all _meta.json files to build ordering map
        $metaFiles = collect($tree)
            ->filter(fn ($item) => str_ends_with($item['path'], '_meta.json'))
            ->pluck('path')
            ->toArray();

        $metaData = $this->loadMetaFiles($client, $metaFiles);

        // Also load docs-config.json for root navigation ordering
        $docsConfig = $this->loadDocsConfig($client);
        if ($docsConfig) {
            $metaData['_root'] = $docsConfig;
        }

        // Set meta data on importer for use during import
        $this->importer->setMetaData($metaData);

        // Update existing navigation/group ordering from meta data
        $this->importer->updateNavigationOrdering();

        // Get all markdown files, excluding reserved folders
        $markdownFiles = collect($tree)
            ->filter(fn ($item) => str_ends_with($item['path'], '.md'))
            ->filter(fn ($item) => ! $this->isInReservedFolder($item['path']))
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

        return [
            'processed' => $processedPaths,
            'deleted' => array_fill(0, $deleted['documents'] ?? 0, 'deleted'),
        ];
    }

    private function performDifferentialSync(
        GitHubApiClient $client,
        string $fromCommit,
        array $latestCommit
    ): array {
        // Get changed files between commits
        $changedFiles = $client->getChangedFiles($fromCommit, $latestCommit['sha']);

        // Load all meta files for ordering (always needed for proper hierarchy)
        $tree = $client->getDirectoryTree('docs');
        $metaFiles = collect($tree)
            ->filter(fn ($item) => str_ends_with($item['path'], '_meta.json'))
            ->pluck('path')
            ->toArray();

        $metaData = $this->loadMetaFiles($client, $metaFiles);

        // Also load docs-config.json for root navigation ordering
        $docsConfig = $this->loadDocsConfig($client);
        if ($docsConfig) {
            $metaData['_root'] = $docsConfig;
        }

        // Set meta data on importer for use during import
        $this->importer->setMetaData($metaData);

        // Update existing navigation/group ordering from meta data
        $this->importer->updateNavigationOrdering();

        $processedPaths = [];
        $deletedPaths = [];

        foreach ($changedFiles as $file) {
            $path = $file['filename'];

            // Only process markdown files in docs directory, excluding reserved folders
            if (! str_ends_with($path, '.md') || ! str_starts_with($path, 'docs/') || $this->isInReservedFolder($path)) {
                continue;
            }

            if ($file['status'] === 'removed') {
                // File was deleted - remove from database
                $page = Page::where('git_path', $path)->where('source', 'git')->first();
                if ($page) {
                    $page->delete();
                    $deletedPaths[] = $path;
                    Log::info("Deleted page for removed file: {$path}");
                }
            } else {
                // File was added or modified - fetch and update
                try {
                    $content = $client->getFileContent($path);

                    if ($content) {
                        $parsed = $this->parser->parse($content, $path);
                        $this->importer->import($parsed, $latestCommit);
                        $processedPaths[] = $path;
                        Log::info("Processed {$file['status']} file: {$path}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to process file {$path}: ".$e->getMessage());
                }
            }
        }

        // Clean up orphaned groups/navigation pages
        if (count($deletedPaths) > 0) {
            $this->importer->cleanupOrphanedPages();
        }

        return [
            'processed' => $processedPaths,
            'deleted' => $deletedPaths,
        ];
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

    private function loadMetaFiles(GitHubApiClient $client, array $metaPaths): array
    {
        $metaData = [];

        foreach ($metaPaths as $path) {
            try {
                $content = $client->getFileContent($path);
                if ($content) {
                    $parsed = $this->parser->parseMetaFile($content);
                    // Key by directory path (e.g., 'docs/documentation' => meta data)
                    $dirPath = dirname($path);
                    $metaData[$dirPath] = $parsed;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to load meta file {$path}: ".$e->getMessage());
            }
        }

        return $metaData;
    }

    private function loadDocsConfig(GitHubApiClient $client): ?array
    {
        try {
            $content = $client->getFileContent('docs/docs-config.json');
            if ($content) {
                return json_decode($content, true);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load docs-config.json: '.$e->getMessage());
        }

        return null;
    }

    private function isInReservedFolder(string $path): bool
    {
        $parts = explode('/', $path);

        // Path format: docs/[folder]/...
        // Check if second segment is a reserved folder
        if (count($parts) >= 2 && in_array($parts[1], self::RESERVED_FOLDERS, true)) {
            return true;
        }

        return false;
    }
}
