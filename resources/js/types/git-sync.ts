export interface GitSync {
  id: number;
  commit_hash: string;
  commit_message: string;
  commit_author: string;
  commit_date: string;
  status: 'success' | 'failed' | 'in_progress';
  files_changed: number;
  error_message: string | null;
  sync_details: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
}

export interface SystemConfig {
  content_mode: 'git' | 'cms';
  git_repository_url: string | null;
  git_branch: string;
  git_sync_frequency: number;
  last_synced_at: string | null;
  git_access_token_configured: boolean;
  git_webhook_secret_configured: boolean;
  setup_completed: boolean;
  webhook_url: string;
}
