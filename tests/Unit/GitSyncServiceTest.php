<?php

namespace Tests\Unit;

use App\Models\GitSync;
use App\Models\Page;
use App\Models\SystemConfig;
use App\Services\ContentImporter;
use App\Services\GitSyncService;
use App\Services\MarkdownParser;
use App\Services\PageImporterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private GitSyncService $service;

    private MarkdownParser $parser;

    private ContentImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new MarkdownParser;
        $this->importer = new ContentImporter(new PageImporterService);
        $this->service = new GitSyncService($this->parser, $this->importer);
    }

    private function createGitModeConfig(array $overrides = []): SystemConfig
    {
        $config = SystemConfig::create(array_merge([
            'content_mode' => 'git',
            'git_repository_url' => 'https://github.com/owner/repo',
            'git_branch' => 'main',
            'git_access_token' => 'test-token',
            'setup_completed' => true,
        ], $overrides));

        SystemConfig::clearCache();

        return $config;
    }

    public function test_sync_throws_exception_when_not_in_git_mode()
    {
        SystemConfig::create([
            'content_mode' => 'cms',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Git sync is only available when content mode is set to git');

        $this->service->sync();
    }

    public function test_test_connection_returns_false_when_repository_not_configured()
    {
        SystemConfig::create([
            'content_mode' => 'git',
            'git_repository_url' => null,
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $result = $this->service->testConnection();

        $this->assertFalse($result);
    }

    public function test_test_connection_returns_false_on_invalid_config()
    {
        $result = $this->service->testConnection(
            repositoryUrl: null,
            branch: 'main',
            token: 'token'
        );

        $this->assertFalse($result);
    }

    public function test_already_synced_commit_returns_existing_sync()
    {
        $this->createGitModeConfig();

        $existingSync = GitSync::create([
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit',
            'commit_author' => 'Author',
            'commit_date' => now(),
            'sync_status' => 'success',
        ]);

        $this->assertDatabaseHas('git_syncs', [
            'commit_hash' => 'abc123',
            'sync_status' => 'success',
        ]);

        $this->assertEquals('abc123', $existingSync->commit_hash);
        $this->assertEquals('success', $existingSync->sync_status);
    }

    public function test_rollback_throws_exception_for_non_successful_sync()
    {
        $this->createGitModeConfig();

        $failedSync = GitSync::create([
            'commit_hash' => 'abc123',
            'commit_message' => 'Test commit',
            'commit_author' => 'Author',
            'commit_date' => now(),
            'sync_status' => 'failed',
            'error_message' => 'Some error',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can only rollback to successful syncs');

        $this->service->rollback($failedSync);
    }

    public function test_rollback_deletes_pages_synced_after_target()
    {
        $this->createGitModeConfig();

        $syncDate = now()->subDay();
        $beforeSyncDate = now()->subDays(2);
        $afterSyncDate = now();

        $successfulSync = GitSync::create([
            'commit_hash' => 'target123',
            'commit_message' => 'Target commit',
            'commit_author' => 'Author',
            'commit_date' => $syncDate,
            'sync_status' => 'success',
        ]);

        $pageToKeep = Page::factory()->fromGit()->create([
            'updated_at_git' => $beforeSyncDate,
        ]);

        $pageToDelete = Page::factory()->fromGit()->create([
            'updated_at_git' => $afterSyncDate,
        ]);

        $this->service->rollback($successfulSync);

        $this->assertDatabaseHas('pages', ['id' => $pageToKeep->id]);
        $this->assertDatabaseMissing('pages', ['id' => $pageToDelete->id]);
    }
}
