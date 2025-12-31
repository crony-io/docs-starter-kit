<?php

namespace App\Console\Commands;

use App\Services\GitSyncService;
use Illuminate\Console\Command;

class DocsSync extends Command
{
    protected $signature = 'docs:sync {--force : Force full re-sync}';

    protected $description = 'Sync documentation from Git repository';

    public function handle(GitSyncService $syncService): int
    {
        if (! config('docs.git_enabled', true)) {
            $this->error('Git sync is disabled');

            return self::FAILURE;
        }

        $this->info('Starting Git sync...');

        try {
            $sync = $syncService->sync();

            if ($sync->isSuccess()) {
                $this->info('✓ Sync completed successfully!');
                $this->table(
                    ['Attribute', 'Value'],
                    [
                        ['Commit', substr($sync->commit_hash, 0, 7)],
                        ['Author', $sync->commit_author],
                        ['Message', $sync->commit_message],
                        ['Files Changed', $sync->files_changed],
                    ]
                );

                return self::SUCCESS;
            }

            $this->error('Sync failed: '.$sync->error_message);

            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error('Sync failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
