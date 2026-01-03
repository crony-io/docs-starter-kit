export interface ServerCompatibility {
  proc_open: {
    available: boolean;
    reason: string | null;
  };
  max_execution_time: number;
  php_version: string;
  pending_jobs: number;
  failed_jobs: number;
  queue_driver: string;
}

export interface WebCronSettings {
  web_cron_enabled: boolean;
  last_web_cron_at: string | null;
}
