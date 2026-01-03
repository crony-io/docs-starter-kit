<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { ConnectionTestResult, GitConfigData } from '@/pages/setup/types';
import { testConnection } from '@/routes/setup';
import { router } from '@inertiajs/vue3';
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  CheckCircle2,
  ExternalLink,
  GitBranch,
  Loader2,
  RefreshCw,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
  modelValue: GitConfigData;
}>();

defineEmits<{
  continue: [];
  back: [];
}>();

const testing = ref(false);
const testResult = ref<ConnectionTestResult | null>(null);

const form = computed(() => props.modelValue);

const updateField = <FieldKey extends keyof GitConfigData>(
  field: FieldKey,
  value: GitConfigData[FieldKey],
) => {
  (props.modelValue as GitConfigData)[field] = value;
};

const isValid = computed(() => {
  return form.value.repositoryUrl && form.value.branch;
});

const handleTestConnection = () => {
  testing.value = true;
  testResult.value = null;

  router.post(
    testConnection.url(),
    {
      git_repository_url: form.value.repositoryUrl,
      git_branch: form.value.branch,
      git_access_token: form.value.accessToken,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        testResult.value = { success: true, message: 'There you go!' };
      },
      onError: (errors: Record<string, string>) => {
        testResult.value = {
          success: false,
          message:
            errors.git_repository_url ||
            errors.git_branch ||
            'The connection failed, please check your URL, branch, and/or access token.',
        };
      },
      onFinish: () => {
        testing.value = false;
      },
    },
  );
};
</script>

<template>
  <div class="w-full max-w-2xl space-y-6">
    <div class="text-center">
      <div
        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-500/10"
      >
        <GitBranch class="h-7 w-7 text-blue-500" />
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-foreground">Let's configure your Git Mode.</h1>
      <p class="mt-2 text-muted-foreground">This will sync your documentation by connecting to your GitHub repo
      </p>
    </div>

    <Card class="rounded-xl border-0 shadow-lg">
      <CardHeader class="pb-4">
        <CardTitle class="text-lg">Repository settings</CardTitle>
        <CardDescription>Enter your GitHub repository details to connect it to your documentation</CardDescription>
      </CardHeader>
      <CardContent class="space-y-5">
        <div class="space-y-2">
          <Label for="git_repository_url">Your repository URL *</Label>
          <Input
            id="git_repository_url"
            :model-value="form.repositoryUrl"
            type="url"
            placeholder="https://github.com/username/your-repository"
            required
            class="h-11"
            @update:model-value="updateField('repositoryUrl', $event as string)"
          />
          <p class="text-xs text-muted-foreground">It works for both public and private GitHub repos</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="git_branch">Branch name *</Label>
            <Input
              id="git_branch"
              :model-value="form.branch"
              type="text"
              placeholder="main"
              required
              class="h-11"
              @update:model-value="updateField('branch', $event as string)"
            />
          </div>

          <div class="space-y-2">
            <Label for="git_sync_frequency">Sync frequency</Label>
            <div class="relative">
              <Input
                id="git_sync_frequency"
                :model-value="form.syncFrequency"
                type="number"
                min="5"
                max="1440"
                class="h-11 pr-16"
                @update:model-value="updateField('syncFrequency', Number($event))"
              />
              <span class="absolute top-1/2 right-3 -translate-y-1/2 text-xs text-muted-foreground">
                minutes
              </span>
            </div>
          </div>
        </div>

        <Separator />

        <div class="space-y-2">
          <Label for="git_access_token">Access token</Label>
          <Input
            id="git_access_token"
            :model-value="form.accessToken"
            type="password"
            placeholder="ghp_xxxxxxxxxxxx"
            class="h-11 font-mono"
            @update:model-value="updateField('accessToken', $event as string)"
          />
          <p class="flex items-center gap-1 text-xs text-muted-foreground">
            Required only for private repositories. You can
            <a
              href="https://github.com/settings/tokens"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-0.5 text-primary hover:underline"
            >
              generate a token here
              <ExternalLink class="h-3 w-3" />
            </a>
          </p>
        </div>

        <div class="space-y-2">
          <Label for="git_webhook_secret">Webhook secret</Label>
          <Input
            id="git_webhook_secret"
            :model-value="form.webhookSecret"
            type="text"
            placeholder="Optional, for GitHub Actions"
            class="h-11"
            @update:model-value="updateField('webhookSecret', $event as string)"
          />
          <p class="text-xs text-muted-foreground">
            This allows instant updates via webhook. Configure it in your GitHub repository settings.
          </p>
        </div>

        <!-- Connection Test -->
        <div class="rounded-lg border bg-muted/50 p-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-sm font-medium">Test connection</p>
              <p class="text-xs text-muted-foreground">Verify your repository settings</p>
            </div>
            <Button
              type="button"
              variant="outline"
              size="sm"
              :disabled="!isValid || testing"
              @click="handleTestConnection"
            >
              <Loader2 v-if="testing" class="mr-2 h-4 w-4 animate-spin" />
              <RefreshCw v-else class="mr-2 h-4 w-4" />
              {{ testing ? 'Testing...' : 'Test' }}
            </Button>
          </div>

          <Alert
            v-if="testResult"
            class="mt-3"
            :variant="testResult.success ? 'default' : 'destructive'"
          >
            <CheckCircle2 v-if="testResult.success" class="h-4 w-4 text-green-500" />
            <AlertCircle v-else class="h-4 w-4" />
            <AlertDescription>{{ testResult.message }}</AlertDescription>
          </Alert>
        </div>
      </CardContent>
    </Card>

    <div class="flex items-center justify-between pt-2">
      <Button variant="ghost" size="sm" class="text-muted-foreground" @click="$emit('back')">
        <ArrowLeft class="mr-2 h-4 w-4" />
        Back
      </Button>
      <Button :disabled="!isValid" class="h-11 px-6" @click="$emit('continue')">
        Continue
        <ArrowRight class="ml-2 h-4 w-4" />
      </Button>
    </div>
  </div>
</template>