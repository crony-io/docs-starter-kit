<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Check if session driver is database
            if (config('session.driver') !== 'database') {
                $this->info('Session driver is not database. Skipping cleanup.');

                return 0;
            }

            // Test database connection first
            DB::connection()->getPdo();

            $lifetime = config('session.lifetime') * 60;
            $expireTime = Carbon::now()->subSeconds($lifetime)->timestamp;

            $deletedCount = DB::table('sessions')
                ->where('last_activity', '<', $expireTime)
                ->delete();

            $this->info("Expired sessions cleaned up. Deleted: {$deletedCount} sessions.");

            return 0;

        } catch (\PDOException $e) {
            $this->error('Database connection failed: '.$e->getMessage());

            return 1;
        } catch (\Exception $e) {
            $this->error('Error cleaning sessions: '.$e->getMessage());

            return 1;
        }
    }
}
