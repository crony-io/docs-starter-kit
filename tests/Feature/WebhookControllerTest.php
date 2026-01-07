<?php

namespace Tests\Feature;

use App\Jobs\SyncGitRepositoryJob;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        // Setup requires at least one user to be "complete"
        User::factory()->create();
    }

    private function createGitModeConfig(array $overrides = []): void
    {
        SystemConfig::create(array_merge([
            'content_mode' => 'git',
            'git_repository_url' => 'https://github.com/owner/repo',
            'git_branch' => 'main',
            'git_webhook_secret' => 'test-secret',
            'setup_completed' => true,
        ], $overrides));

        SystemConfig::clearCache();
    }

    private function generateSignature(string $payload, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $secret);
    }

    public function test_webhook_returns_200_when_git_mode_not_enabled()
    {
        SystemConfig::create([
            'content_mode' => 'cms',
            'setup_completed' => true,
        ]);
        SystemConfig::clearCache();

        $response = $this->postJson(route('webhook.github'), []);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Git mode not enabled']);

        Queue::assertNotPushed(SyncGitRepositoryJob::class);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $this->createGitModeConfig();

        $payload = json_encode(['ref' => 'refs/heads/main']);

        $response = $this->postJson(
            route('webhook.github'),
            json_decode($payload, true),
            [
                'X-GitHub-Event' => 'push',
                'X-Hub-Signature-256' => 'sha256=invalid-signature',
            ]
        );

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);

        Queue::assertNotPushed(SyncGitRepositoryJob::class);
    }

    public function test_webhook_rejects_missing_signature()
    {
        $this->createGitModeConfig();

        $response = $this->postJson(
            route('webhook.github'),
            ['ref' => 'refs/heads/main'],
            ['X-GitHub-Event' => 'push']
        );

        $response->assertStatus(401);

        Queue::assertNotPushed(SyncGitRepositoryJob::class);
    }

    public function test_webhook_ignores_non_push_events()
    {
        $this->createGitModeConfig();

        $payload = json_encode(['action' => 'opened']);
        $signature = $this->generateSignature($payload, 'test-secret');

        $response = $this->call(
            'POST',
            route('webhook.github'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'pull_request',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Event ignored']);

        Queue::assertNotPushed(SyncGitRepositoryJob::class);
    }

    public function test_webhook_ignores_push_to_different_branch()
    {
        $this->createGitModeConfig(['git_branch' => 'main']);

        $payload = json_encode(['ref' => 'refs/heads/develop', 'commits' => []]);
        $signature = $this->generateSignature($payload, 'test-secret');

        $response = $this->call(
            'POST',
            route('webhook.github'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Push to different branch ignored']);

        Queue::assertNotPushed(SyncGitRepositoryJob::class);
    }

    public function test_webhook_queues_sync_job_for_valid_push()
    {
        $this->createGitModeConfig(['git_branch' => 'main']);

        $payload = json_encode([
            'ref' => 'refs/heads/main',
            'commits' => [
                ['id' => 'abc123', 'message' => 'Test commit'],
            ],
        ]);
        $signature = $this->generateSignature($payload, 'test-secret');

        $response = $this->call(
            'POST',
            route('webhook.github'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Sync queued']);

        Queue::assertPushed(SyncGitRepositoryJob::class);
    }

    public function test_webhook_returns_401_when_secret_not_configured()
    {
        $this->createGitModeConfig(['git_webhook_secret' => null]);

        $payload = json_encode(['ref' => 'refs/heads/main']);
        $signature = $this->generateSignature($payload, 'any-secret');

        $response = $this->call(
            'POST',
            route('webhook.github'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $response->assertStatus(401);

        Queue::assertNotPushed(SyncGitRepositoryJob::class);
    }
}
