<?php

namespace App\Jobs;

use App\Services\GitSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGitRepositoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300; // 5 minutes

    public function handle(GitSyncService $syncService): void
    {
        try {
            $syncService->sync();
        } catch (\Exception $e) {
            Log::error('Git sync failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
