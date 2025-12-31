<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\FeedbackResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnonymizeOldIpAddresses extends Command
{
    protected $signature = 'privacy:anonymize-ips {--days=30 : Number of days after which to anonymize IPs}';

    protected $description = 'Anonymize IP addresses older than specified days for GDPR compliance';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Anonymizing IP addresses older than {$days} days (before {$cutoffDate->toDateString()})...");

        try {
            DB::transaction(function () use ($cutoffDate) {
                $activityLogsUpdated = ActivityLog::where('created_at', '<', $cutoffDate)
                    ->whereNotNull('ip_address')
                    ->where('ip_address', '!=', 'anonymized')
                    ->update([
                        'ip_address' => 'anonymized',
                        'real_ip' => null,
                        'ip_info' => null,
                    ]);

                $feedbackUpdated = FeedbackResponse::where('created_at', '<', $cutoffDate)
                    ->whereNotNull('ip_address')
                    ->where('ip_address', '!=', 'anonymized')
                    ->update([
                        'ip_address' => 'anonymized',
                        'user_agent' => null,
                    ]);

                $this->info("Anonymized {$activityLogsUpdated} activity log entries.");
                $this->info("Anonymized {$feedbackUpdated} feedback responses.");

                Log::info('IP anonymization completed', [
                    'activity_logs' => $activityLogsUpdated,
                    'feedback_responses' => $feedbackUpdated,
                    'cutoff_date' => $cutoffDate->toDateString(),
                ]);
            });

            $this->info('IP anonymization completed successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to anonymize IP addresses: {$e->getMessage()}");
            Log::error('IP anonymization failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
