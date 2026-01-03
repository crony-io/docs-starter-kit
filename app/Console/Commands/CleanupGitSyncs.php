<?php

namespace App\Console\Commands;

use App\Models\GitSync;
use Illuminate\Console\Command;

class CleanupGitSyncs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git-sync:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old git syncs (keep last 100)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = GitSync::orderBy('created_at', 'desc')
            ->skip(100)
            ->delete();

        $this->info("Cleaned up {$deletedCount} old git sync records.");

        return self::SUCCESS;
    }
}
