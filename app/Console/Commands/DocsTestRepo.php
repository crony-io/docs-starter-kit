<?php

namespace App\Console\Commands;

use App\Services\GitSyncService;
use Illuminate\Console\Command;

class DocsTestRepo extends Command
{
    protected $signature = 'docs:test-repo';

    protected $description = 'Test connection to Git repository';

    public function handle(GitSyncService $syncService): int
    {
        $this->info('Testing repository connection...');

        try {
            $success = $syncService->testConnection();

            if ($success) {
                $this->info('✓ Successfully connected to repository');

                return self::SUCCESS;
            }

            $this->error('✗ Failed to connect to repository');

            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error('Connection test failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
