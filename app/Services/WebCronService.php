<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebCronService
{
    /**
     * Run the Laravel scheduler.
     *
     * If proc_open is available, runs in background (non-blocking).
     * Otherwise, runs synchronously (may delay page load).
     */
    public function runScheduler(): bool
    {
        if ($this->isAsyncSupported()) {
            return $this->runAsync();
        }

        return $this->runSync();
    }

    /**
     * Run scheduler asynchronously in background process.
     */
    private function runAsync(): bool
    {
        try {
            $artisan = base_path('artisan');

            if (DIRECTORY_SEPARATOR === '\\') {
                // Windows: use popen for true background execution
                $command = sprintf('start /B %s %s schedule:run', PHP_BINARY, $artisan);
                pclose(popen($command, 'r'));
            } else {
                // Unix: redirect output and background
                $command = sprintf('%s %s schedule:run >> /dev/null 2>&1 &', PHP_BINARY, $artisan);
                exec($command);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Web-cron: Async execution failed, falling back to sync', [
                'error' => $e->getMessage(),
            ]);

            return $this->runSync();
        }
    }

    /**
     * Run scheduler synchronously (blocks until complete).
     * Used when proc_open is not available.
     */
    private function runSync(): bool
    {
        try {
            Artisan::call('schedule:run');

            return true;
        } catch (\Exception $e) {
            Log::error('Web-cron: Scheduler failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if async (background) execution is supported.
     */
    public function isAsyncSupported(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $disabled = explode(',', ini_get('disable_functions'));

        return ! in_array('proc_open', array_map('trim', $disabled));
    }

    /**
     * Get server compatibility information for the UI.
     */
    public function getServerCompatibility(): array
    {
        return [
            'proc_open' => $this->checkProcOpen(),
            'max_execution_time' => (int) ini_get('max_execution_time'),
            'php_version' => PHP_VERSION,
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'queue_driver' => config('queue.default'),
        ];
    }

    private function checkProcOpen(): array
    {
        $available = function_exists('proc_open');
        $disabled = in_array('proc_open', explode(',', ini_get('disable_functions')));

        return [
            'available' => $available && ! $disabled,
            'reason' => ! $available ? 'Function does not exist' : ($disabled ? 'Disabled in php.ini' : null),
        ];
    }

    private function getPendingJobsCount(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception) {
            return -1;
        }
    }

    private function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception) {
            return -1;
        }
    }
}
