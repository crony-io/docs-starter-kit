<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
  index as gitSyncIndex,
  manual,
  rollback,
  test,
  config as updateConfig,
} from '@/routes/admin/git-sync';
import type { BreadcrumbItem } from '@/types';
import type { GitSync, SystemConfig } from '@/types/git-sync';
import { Form, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
  config: SystemConfig;
  syncs: GitSync[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Git Sync', href: gitSyncIndex().url },
];

const gitRepositoryUrl = ref<string>(props.config.git_repository_url ?? '');
const gitBranch = ref<string>(props.config.git_branch ?? 'main');
const gitSyncFrequency = ref<number>(props.config.git_sync_frequency ?? 15);

const gitAccessToken = ref<string>('');
const clearGitAccessToken = ref<boolean>(false);

const gitWebhookSecret = ref<string>('');
const clearGitWebhookSecret = ref<boolean>(false);

const hasGitConfigured = computed(() => {
  return gitRepositoryUrl.value.trim().length > 0 && gitBranch.value.trim().length > 0;
});

const statusBadgeVariant = (status: GitSync['status']): 'default' | 'secondary' | 'destructive' => {
  if (status === 'success') {
    return 'default';
  }

  if (status === 'failed') {
    return 'destructive';
  }

  return 'secondary';
};

const shortSha = (sha: string) => sha.slice(0, 7);

const testConnection = () => {
  router.post(
    test(),
    {
      git_repository_url: gitRepositoryUrl.value,
      git_branch: gitBranch.value,
      git_access_token: gitAccessToken.value,
    },
    {
      preserveScroll: true,
    },
  );
};
</script>

<template>
  <Head title="Git Sync" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex items-start justify-between gap-4">
        <Heading
          title="Git Sync"
          description="Sync documentation from a GitHub repository, configure webhooks, and review sync history"
        />

        <Form v-bind="manual.form()" v-slot="{ processing }">
          <Button type="submit" :disabled="processing || !hasGitConfigured">
            {{ processing ? 'Queueing…' : 'Run sync now' }}
          </Button>
        </Form>
      </div>

      <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Repository Configuration</CardTitle>
          </CardHeader>
          <CardContent>
            <Form v-bind="updateConfig.form()" v-slot="{ errors, processing }" class="space-y-6">
              <div class="space-y-2">
                <Label for="git_repository_url">Repository URL</Label>
                <Input
                  id="git_repository_url"
                  v-model="gitRepositoryUrl"
                  name="git_repository_url"
                  type="url"
                  placeholder="https://github.com/owner/repo"
                  autocomplete="off"
                  required
                />
                <InputError :message="errors.git_repository_url" />
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="git_branch">Branch</Label>
                  <Input
                    id="git_branch"
                    v-model="gitBranch"
                    name="git_branch"
                    type="text"
                    placeholder="main"
                    autocomplete="off"
                    required
                  />
                  <InputError :message="errors.git_branch" />
                </div>

                <div class="space-y-2">
                  <Label for="git_sync_frequency">Sync frequency (minutes)</Label>
                  <Input
                    id="git_sync_frequency"
                    v-model="gitSyncFrequency"
                    name="git_sync_frequency"
                    type="number"
                    :min="5"
                    :max="1440"
                    required
                  />
                  <InputError :message="errors.git_sync_frequency" />
                </div>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <div class="flex items-center justify-between gap-3">
                    <Label for="git_access_token">Access token</Label>
                    <Badge v-if="config.git_access_token_configured" variant="secondary">
                      Configured
                    </Badge>
                    <Badge v-else variant="outline">Not set</Badge>
                  </div>

                  <Input
                    id="git_access_token"
                    v-model="gitAccessToken"
                    name="git_access_token"
                    type="password"
                    placeholder="Leave empty to keep current"
                    autocomplete="off"
                  />
                  <InputError :message="errors.git_access_token" />

                  <div class="flex items-center gap-2">
                    <Checkbox id="clear_git_access_token" v-model:checked="clearGitAccessToken" />
                    <Label for="clear_git_access_token">Clear token</Label>
                    <input
                      type="hidden"
                      name="clear_git_access_token"
                      :value="clearGitAccessToken ? 1 : 0"
                    />
                  </div>
                </div>

                <div class="space-y-2">
                  <div class="flex items-center justify-between gap-3">
                    <Label for="git_webhook_secret">Webhook secret</Label>
                    <Badge v-if="config.git_webhook_secret_configured" variant="secondary">
                      Configured
                    </Badge>
                    <Badge v-else variant="outline">Not set</Badge>
                  </div>

                  <Input
                    id="git_webhook_secret"
                    v-model="gitWebhookSecret"
                    name="git_webhook_secret"
                    type="password"
                    placeholder="Leave empty to keep current"
                    autocomplete="off"
                  />
                  <InputError :message="errors.git_webhook_secret" />

                  <div class="flex items-center gap-2">
                    <Checkbox
                      id="clear_git_webhook_secret"
                      v-model:checked="clearGitWebhookSecret"
                    />
                    <Label for="clear_git_webhook_secret">Clear secret</Label>
                    <input
                      type="hidden"
                      name="clear_git_webhook_secret"
                      :value="clearGitWebhookSecret ? 1 : 0"
                    />
                  </div>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-3">
                <Button type="submit" :disabled="processing">{{
                  processing ? 'Saving…' : 'Save'
                }}</Button>

                <Button
                  type="button"
                  variant="outline"
                  :disabled="!hasGitConfigured"
                  @click="testConnection"
                >
                  Test connection
                </Button>

                <div class="text-sm text-muted-foreground">
                  <span class="font-medium">Webhook URL:</span>
                  <span class="ml-2 select-all">{{ config.webhook_url }}</span>
                </div>

                <div v-if="config.last_synced_at" class="text-sm text-muted-foreground">
                  <span class="font-medium">Last synced:</span>
                  <span class="ml-2">{{ new Date(config.last_synced_at).toLocaleString() }}</span>
                </div>
              </div>
            </Form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Status</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-muted-foreground">Mode</span>
              <Badge :variant="config.content_mode === 'git' ? 'default' : 'secondary'">
                {{ config.content_mode.toUpperCase() }}
              </Badge>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-muted-foreground">Setup complete</span>
              <Badge :variant="config.setup_completed ? 'default' : 'secondary'">
                {{ config.setup_completed ? 'Yes' : 'No' }}
              </Badge>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Recent syncs</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Status</TableHead>
                <TableHead>Commit</TableHead>
                <TableHead>Message</TableHead>
                <TableHead>Files</TableHead>
                <TableHead>Date</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="sync in syncs" :key="sync.id">
                <TableCell>
                  <Badge :variant="statusBadgeVariant(sync.status)">{{ sync.status }}</Badge>
                </TableCell>
                <TableCell class="font-mono text-sm">{{ shortSha(sync.commit_hash) }}</TableCell>
                <TableCell class="max-w-[420px] truncate">{{ sync.commit_message }}</TableCell>
                <TableCell>{{ sync.files_changed }}</TableCell>
                <TableCell class="text-muted-foreground">
                  {{ new Date(sync.created_at).toLocaleString() }}
                </TableCell>
                <TableCell class="text-right">
                  <Form v-bind="rollback.form(sync.id)" v-slot="{ processing }">
                    <Button
                      type="submit"
                      size="sm"
                      variant="outline"
                      :disabled="processing || sync.status !== 'success'"
                    >
                      {{ processing ? 'Rolling back…' : 'Rollback' }}
                    </Button>
                  </Form>
                </TableCell>
              </TableRow>

              <TableRow v-if="syncs.length === 0">
                <TableCell colspan="6" class="py-8 text-center text-sm text-muted-foreground">
                  No syncs yet.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
