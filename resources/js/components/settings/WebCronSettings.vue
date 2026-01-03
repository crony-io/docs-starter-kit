<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { ServerCompatibility } from '@/types/web-cron';
import { AlertTriangle, CheckCircle, Info } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  modelValue: boolean;
  lastWebCronAt: string | null;
  serverCheck: ServerCompatibility;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();

const checked = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
});

const formatDate = (dateString: string | null): string => {
  if (!dateString) {
    return 'Never';
  }
  return new Date(dateString).toLocaleString();
};
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Web-Cron Schedule Runner</CardTitle>
      <CardDescription>
        Run all scheduled tasks via visitor traffic (WordPress-style)
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <!-- Server Compatibility Check -->
      <div class="space-y-3">
        <div class="text-sm font-medium">Server Compatibility</div>

        <div class="flex items-center gap-2">
          <CheckCircle v-if="serverCheck.proc_open.available" class="h-4 w-4 text-green-500" />
          <AlertTriangle v-else class="h-4 w-4 text-yellow-500" />
          <span class="text-sm">
            Background Execution:
            {{ serverCheck.proc_open.available ? 'Supported (async)' : 'Limited (sync)' }}
          </span>
        </div>

        <div class="flex items-center gap-2">
          <CheckCircle
            v-if="serverCheck.queue_driver === 'database'"
            class="h-4 w-4 text-green-500"
          />
          <Info v-else class="h-4 w-4 text-blue-500" />
          <span class="text-sm"> Queue Driver: {{ serverCheck.queue_driver }} </span>
        </div>

        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          Pending Jobs: {{ serverCheck.pending_jobs }} | Failed Jobs: {{ serverCheck.failed_jobs }}
        </div>
      </div>

      <!-- Warning if proc_open not available -->
      <Alert v-if="!serverCheck.proc_open.available" variant="destructive">
        <AlertTriangle class="h-4 w-4" />
        <AlertTitle>Server Limitation</AlertTitle>
        <AlertDescription>
          Your server has <code class="rounded bg-muted px-1">proc_open()</code> disabled. Tasks
          will run synchronously, which may cause brief page delays.
        </AlertDescription>
      </Alert>

      <!-- Enable Toggle -->
      <div class="flex items-center justify-between rounded-lg border p-4">
        <div>
          <Label class="text-base">Enable Web-Cron</Label>
          <p class="text-sm text-muted-foreground">Run all scheduled tasks via visitor requests</p>
        </div>
        <Switch v-model:checked="checked" />
      </div>

      <!-- Status when enabled -->
      <div v-if="modelValue" class="space-y-2 rounded-lg bg-muted p-4 text-sm">
        <div class="flex justify-between">
          <span class="text-muted-foreground">Last Scheduler Run:</span>
          <span>{{ formatDate(lastWebCronAt) }}</span>
        </div>
      </div>

      <!-- Info box -->
      <Alert v-if="modelValue">
        <Info class="h-4 w-4" />
        <AlertTitle>How it works</AlertTitle>
        <AlertDescription>
          <ul class="mt-2 list-inside list-disc space-y-1">
            <li>
              Triggers <code class="rounded bg-muted px-1">schedule:run</code> at most once per
              minute
            </li>
            <li>Laravel decides which tasks are due and runs them</li>
            <li>All future scheduled tasks are automatically included</li>
            <li>Low-traffic sites may experience delayed task execution</li>
          </ul>
        </AlertDescription>
      </Alert>
    </CardContent>
  </Card>
</template>
