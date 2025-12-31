<?php

namespace App\Listeners;

use App\Events\GitSyncFailed;
use Illuminate\Support\Facades\Log;

class StoreSyncFailed
{
    public function handle(GitSyncFailed $event): void
    {
        Log::error('Git sync failed', [
            'sync_id' => $event->sync->id,
            'commit_hash' => $event->sync->commit_hash,
            'error' => $event->exception->getMessage(),
        ]);
    }
}
