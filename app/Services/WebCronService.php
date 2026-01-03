<?php

namespace App\Services;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebCronService
{
    /**
     * Run both scheduler and queue worker.
     *
     * If proc_open is available, runs in background (non-blocking).
     * Otherwise, runs synchronously (may delay page load).
     */
    public function run(): array
    {
        $results = [
            'scheduler' => false,
            'queue' => false,
            'jobs_processed' => 0,
        ];

        // Always use sync for queue (more reliable on Windows/shared hosting)
        // Scheduler can use async if supported
        if ($this->isAsyncSupported()) {
            $results['scheduler'] = $this->runSchedulerAsync();
        } else {
            $results['scheduler'] = $this->runSchedulerSync();
        }

        // Always process queue synchronously for reliability
        $queueResult = $this->runQueueSync();
        $results['queue'] = $queueResult['success'];
        $results['jobs_processed'] = $queueResult['processed'];

        return $results;
    }

    /**
     * Run the Laravel scheduler only.
     *
     * If proc_open is available, runs in background (non-blocking).
     * Otherwise, runs synchronously (may delay page load).
     */
    public function runScheduler(): bool
    {
        if ($this->isAsyncSupported()) {
            return $this->runSchedulerAsync();
        }

        return $this->runSchedulerSync();
    }

    /**
     * Process queue jobs.
     *
     * If proc_open is available, runs in background (non-blocking).
     * Otherwise, processes a limited batch synchronously.
     */
    public function runQueue(): array
    {
        if ($this->isAsyncSupported()) {
            return [
                'success' => $this->runQueueAsync(),
                'processed' => 0, // Unknown when async
            ];
        }

        return $this->runQueueSync();
    }

    /**
     * Run scheduler asynchronously in background process.
     */
    private function runSchedulerAsync(): bool
    {
        try {
            $phpBinary = $this->getPhpBinaryPath();
            $artisan = base_path('artisan');

            if (DIRECTORY_SEPARATOR === '\\') {
                // Windows: use popen for true background execution
                $command = sprintf('start /B %s %s schedule:run', $phpBinary, $artisan);
                pclose(popen($command, 'r'));
            } else {
                // Unix: redirect output and background
                $command = sprintf('%s %s schedule:run >> /dev/null 2>&1 &', $phpBinary, $artisan);
                exec($command);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Web-cron: Async scheduler failed, falling back to sync', [
                'error' => $e->getMessage(),
            ]);

            return $this->runSchedulerSync();
        }
    }

    /**
     * Run queue worker asynchronously in background process.
     * Processes jobs for a limited time then exits.
     */
    private function runQueueAsync(): bool
    {
        try {
            $phpBinary = $this->getPhpBinaryPath();
            $artisan = base_path('artisan');
            // Process jobs for max 60 seconds, then stop
            // --stop-when-empty exits when no jobs remain
            $stopWhenEmpty = '--stop-when-empty';
            $maxTime = '--max-time=60';

            // Process all queues in priority order
            $queues = $this->getActiveQueues();
            $queueArg = count($queues) > 0 ? '--queue='.implode(',', $queues) : '';

            if (DIRECTORY_SEPARATOR === '\\') {
                // Windows: use popen for true background execution
                $command = sprintf('start /B %s %s queue:work %s %s %s', $phpBinary, $artisan, $queueArg, $stopWhenEmpty, $maxTime);
                pclose(popen($command, 'r'));
            } else {
                // Unix: redirect output and background
                $command = sprintf('%s %s queue:work %s %s %s >> /dev/null 2>&1 &', $phpBinary, $artisan, $queueArg, $stopWhenEmpty, $maxTime);
                exec($command);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Web-cron: Async queue worker failed, falling back to sync', [
                'error' => $e->getMessage(),
            ]);

            $result = $this->runQueueSync();

            return $result['success'];
        }
    }

    /**
     * Run scheduler synchronously (blocks until complete).
     * Used when proc_open is not available.
     */
    private function runSchedulerSync(): bool
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
     * Process queue jobs synchronously.
     * Limited to prevent long page loads on shared hosting.
     *
     * @param  int  $maxJobs  Maximum jobs to process (default: 3)
     * @param  int  $maxSeconds  Maximum time in seconds (default: 30)
     */
    private function runQueueSync(int $maxJobs = 3, int $maxSeconds = 30): array
    {
        $result = [
            'success' => true,
            'processed' => 0,
        ];

        try {
            $startTime = time();
            $queueManager = app('queue');
            $connection = $queueManager->connection();

            // Get all active queue names in priority order
            $queueNames = $this->getActiveQueues();
            if (empty($queueNames)) {
                $queueNames = [config('queue.connections.'.config('queue.default').'.queue', 'default')];
            }

            for ($i = 0; $i < $maxJobs; $i++) {
                // Check time limit
                if ((time() - $startTime) >= $maxSeconds) {
                    Log::info('Web-cron: Queue processing stopped due to time limit', [
                        'processed' => $result['processed'],
                        'elapsed' => time() - $startTime,
                    ]);
                    break;
                }

                // Try to get a job from any queue (in priority order)
                $job = null;
                foreach ($queueNames as $queueName) {
                    $job = $connection->pop($queueName);
                    if ($job !== null) {
                        break;
                    }
                }

                if ($job === null) {
                    break;
                }

                try {
                    $this->processJob($job);
                    $result['processed']++;
                } catch (\Exception $e) {
                    Log::error('Web-cron: Job processing failed', [
                        'job' => get_class($job),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Release job back to queue for retry
                    $job->release(60);
                }
            }

        } catch (\Exception $e) {
            Log::error('Web-cron: Queue processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $result['success'] = false;
        }

        return $result;
    }

    /**
     * Process a single queue job.
     */
    private function processJob(Job $job): void
    {
        try {
            $job->fire();
        } catch (\Exception $e) {
            // Mark job as failed if it exceeds max attempts
            if ($job->attempts() >= $job->maxTries()) {
                $job->fail($e);
            } else {
                throw $e;
            }
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
            'base_path' => base_path(),
            'php_binary' => $this->getPhpBinaryPath(),
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

    private function getActiveQueues(): array
    {
        try {
            // Get distinct queue names from jobs table, ordered by priority
            $queues = DB::table('jobs')
                ->select('queue')
                ->distinct()
                ->pluck('queue')
                ->toArray();

            // Sort with high-priority first
            usort($queues, function ($a, $b) {
                if ($a === 'high-priority') {
                    return -1;
                }
                if ($b === 'high-priority') {
                    return 1;
                }
                if ($a === 'default') {
                    return 1;
                }
                if ($b === 'default') {
                    return -1;
                }

                return strcmp($a, $b);
            });

            return $queues;
        } catch (\Exception) {
            return ['default'];
        }
    }

    private function getPhpBinaryPath(): string
    {
        // PHP_BINARY returns Apache path when running as mod_php
        // Check if it looks like a PHP CLI binary
        if (str_contains(PHP_BINARY, 'php')) {
            return PHP_BINARY;
        }

        // Try to find PHP in the same directory as PHP_BINDIR
        $phpPath = PHP_BINDIR.DIRECTORY_SEPARATOR.'php'.(DIRECTORY_SEPARATOR === '\\' ? '.exe' : '');
        if (file_exists($phpPath)) {
            return $phpPath;
        }

        // Fallback to just 'php' and let the system PATH resolve it
        return 'php';
    }
}
