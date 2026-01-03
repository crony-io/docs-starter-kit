<?php

namespace App\Console\Commands;

use App\Models\SystemConfig;
use App\Services\GitSyncService;
use Illuminate\Console\Command;

class DocsSync extends Command
{
    protected $signature = 'docs:sync {--force : Force full re-sync instead of differential}';

    protected $description = 'Sync documentation from Git repository';

    public function handle(GitSyncService $syncService): int
    {

        $config = SystemConfig::where('key', 'content_mode')->first();
        if ($config->value !== 'git') {
            $this->error('Git sync is disabled');

            return self::FAILURE;
        }

        $force = $this->option('force');

        $this->info($force ? 'Starting full Git sync...' : 'Starting differential Git sync...');

        try {
            $sync = $syncService->sync(force: $force);

            if ($sync->isSuccess()) {
                $syncType = $sync->sync_details['sync_type'] ?? 'unknown';
                $this->info("✓ Sync completed successfully! (Type: {$syncType})");
                $this->table(
                    ['Attribute', 'Value'],
                    [
                        ['Commit', substr($sync->commit_hash, 0, 7)],
                        ['Author', $sync->commit_author],
                        ['Message', $sync->commit_message],
                        ['Files Changed', $sync->files_changed],
                        ['Sync Type', $syncType],
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
