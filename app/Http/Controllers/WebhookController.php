<?php

namespace App\Http\Controllers;

use App\Jobs\SyncGitRepositoryJob;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function github(Request $request)
    {
        if (! SystemConfig::isGitMode()) {
            return response()->json(['message' => 'Git mode not enabled'], 200);
        }

        // Verify signature
        if (! $this->verifyGitHubSignature($request)) {
            Log::warning('Invalid GitHub webhook signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Check event type
        $event = $request->header('X-GitHub-Event');

        if ($event !== 'push') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        // Check if it's the configured branch
        $ref = $request->input('ref');
        $config = SystemConfig::instance();
        $configuredBranch = "refs/heads/{$config->git_branch}";

        if ($ref !== $configuredBranch) {
            return response()->json([
                'message' => 'Push to different branch ignored',
            ], 200);
        }

        // Log webhook
        Log::info('GitHub webhook received', [
            'ref' => $ref,
            'commits' => count($request->input('commits', [])),
        ]);

        // Dispatch sync job
        SyncGitRepositoryJob::dispatch()->onQueue('high-priority');

        return response()->json(['message' => 'Sync queued'], 200);
    }

    private function verifyGitHubSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $config = SystemConfig::instance();
        $secret = $config->git_webhook_secret;

        if (! $secret) {
            Log::warning('Webhook secret not configured');

            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
