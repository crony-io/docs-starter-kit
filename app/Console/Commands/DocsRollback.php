<?php

namespace App\Console\Commands;

use App\Models\GitSync;
use App\Services\GitSyncService;
use Illuminate\Console\Command;

class DocsRollback extends Command
{
    protected $signature = 'docs:rollback {sync_id : The ID of the sync to rollback to}';

    protected $description = 'Rollback documentation to a previous sync';

    public function handle(GitSyncService $syncService): int
    {
        $syncId = $this->argument('sync_id');
        $sync = GitSync::find($syncId);

        if (! $sync) {
            $this->error("Sync with ID {$syncId} not found");

            return self::FAILURE;
        }

        if (! $sync->isSuccess()) {
            $this->error('Can only rollback to successful syncs');

            return self::FAILURE;
        }

        if (! $this->confirm("Rollback to commit {$sync->commit_hash}?")) {
            $this->info('Rollback cancelled');

            return self::SUCCESS;
        }

        try {
            $syncService->rollback($sync);
            $this->info('✓ Rollback completed successfully');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Rollback failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
