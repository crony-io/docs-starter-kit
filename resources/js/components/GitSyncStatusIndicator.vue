<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { index as gitSyncIndex } from '@/routes/admin/git-sync';
import type { GitSyncStatus } from '@/types/git-sync';
import { AlertCircle, CheckCircle2, GitBranch, Loader2, RefreshCw } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  gitSyncStatus: GitSyncStatus | null;
}

const props = defineProps<Props>();

const formatTimeAgo = (dateString: string | null): string => {
  if (!dateString) {
    return 'Never';
  }

  const date = new Date(dateString);
  const now = new Date();
  const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

  if (seconds < 60) {
    return 'just now';
  }
  if (seconds < 3600) {
    return `${Math.floor(seconds / 60)} minutes ago`;
  }
  if (seconds < 86400) {
    return `${Math.floor(seconds / 3600)} hours ago`;
  }
  if (seconds < 2592000) {
    return `${Math.floor(seconds / 86400)} days ago`;
  }
  return date.toLocaleDateString();
};

const statusIcon = computed(() => {
  if (!props.gitSyncStatus?.lastSync) {
    return GitBranch;
  }

  switch (props.gitSyncStatus.lastSync.status) {
    case 'success':
      return CheckCircle2;
    case 'failed':
      return AlertCircle;
    case 'in_progress':
      return Loader2;
    default:
      return GitBranch;
  }
});

const statusColor = computed(() => {
  if (!props.gitSyncStatus?.lastSync) {
    return 'text-muted-foreground';
  }

  switch (props.gitSyncStatus.lastSync.status) {
    case 'success':
      return 'text-green-500';
    case 'failed':
      return 'text-red-500';
    case 'in_progress':
      return 'text-blue-500 animate-spin';
    default:
      return 'text-muted-foreground';
  }
});

const lastSyncedAgo = computed(() => {
  return formatTimeAgo(props.gitSyncStatus?.lastSyncedAt ?? null);
});

const statusText = computed(() => {
  if (!props.gitSyncStatus?.lastSync) {
    return 'No sync yet';
  }

  switch (props.gitSyncStatus.lastSync.status) {
    case 'success':
      return 'Synced';
    case 'failed':
      return 'Sync failed';
    case 'in_progress':
      return 'Syncing...';
    default:
      return 'Unknown';
  }
});
</script>

<template>
  <Popover v-if="props.gitSyncStatus?.enabled">
    <PopoverTrigger as-child>
      <Button variant="ghost" size="sm" class="gap-2" :title="`Git Sync: ${statusText}`">
        <component :is="statusIcon" class="h-4 w-4" :class="statusColor" />
        <span class="hidden text-xs text-muted-foreground sm:inline">
          {{ lastSyncedAgo }}
        </span>
      </Button>
    </PopoverTrigger>
    <PopoverContent align="end" class="w-80">
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="font-medium">Git Sync Status</h4>
          <span
            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
            :class="{
              'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400':
                props.gitSyncStatus?.lastSync?.status === 'success',
              'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400':
                props.gitSyncStatus?.lastSync?.status === 'failed',
              'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400':
                props.gitSyncStatus?.lastSync?.status === 'in_progress',
            }"
          >
            <component
              :is="statusIcon"
              class="h-3 w-3"
              :class="{ 'animate-spin': props.gitSyncStatus?.lastSync?.status === 'in_progress' }"
            />
            {{ statusText }}
          </span>
        </div>

        <div v-if="props.gitSyncStatus?.lastSync" class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Last sync:</span>
            <span>{{ lastSyncedAgo }}</span>
          </div>

          <div v-if="props.gitSyncStatus.lastSync.commitHash" class="flex justify-between">
            <span class="text-muted-foreground">Commit:</span>
            <code class="rounded bg-muted px-1.5 py-0.5 text-xs">
              {{ props.gitSyncStatus.lastSync.commitHash }}
            </code>
          </div>

          <div v-if="props.gitSyncStatus.lastSync.filesChanged" class="flex justify-between">
            <span class="text-muted-foreground">Files changed:</span>
            <span>{{ props.gitSyncStatus.lastSync.filesChanged }}</span>
          </div>

          <div
            v-if="props.gitSyncStatus.lastSync.commitMessage"
            class="rounded-md bg-muted/50 p-2 text-xs"
          >
            <p class="line-clamp-2">{{ props.gitSyncStatus.lastSync.commitMessage }}</p>
          </div>

          <div
            v-if="
              props.gitSyncStatus.lastSync.status === 'failed' && props.gitSyncStatus.lastSync.error
            "
            class="rounded-md bg-red-50 p-2 text-xs text-red-600 dark:bg-red-900/20 dark:text-red-400"
          >
            {{ props.gitSyncStatus.lastSync.error }}
          </div>
        </div>

        <div v-else class="text-center text-sm text-muted-foreground">
          No sync has been performed yet.
        </div>

        <Button variant="outline" size="sm" class="w-full" as-child>
          <a :href="gitSyncIndex().url">
            <RefreshCw class="mr-2 h-4 w-4" />
            Manage Git Sync
          </a>
        </Button>
      </div>
    </PopoverContent>
  </Popover>
</template>
