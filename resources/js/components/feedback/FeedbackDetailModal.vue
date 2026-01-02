<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import type { FeedbackFormField, FeedbackResponse } from '@/types/feedback';
import { Calendar, FileText, Globe, Monitor, ThumbsDown, ThumbsUp, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  response: FeedbackResponse | null;
  open: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

// Get form name from structured form_data
const formName = computed(() => props.response?.form_data?.form_name ?? null);

// Get fields with their responses, using the snapshot for labels and types
const formDataEntries = computed(() => {
  const formData = props.response?.form_data;
  if (!formData?.responses) {
    return [];
  }

  // If we have field definitions, use them for proper labels
  if (formData.fields?.length) {
    return formData.fields.map((field: FeedbackFormField) => {
      const value = formData.responses[field.label];
      return {
        label: field.label,
        type: field.type,
        value: Array.isArray(value) ? value.join(', ') : String(value ?? ''),
      };
    });
  }

  // Fallback: just show responses as key-value pairs
  return Object.entries(formData.responses).map(([key, value]) => ({
    label: key,
    type: 'text',
    value: Array.isArray(value) ? value.join(', ') : String(value ?? ''),
  }));
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const close = () => emit('update:open', false);
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <ThumbsUp v-if="response?.is_helpful" class="h-5 w-5 text-green-500" />
          <ThumbsDown v-else class="h-5 w-5 text-red-500" />
          Feedback Details
        </DialogTitle>
        <DialogDescription>
          {{ response?.is_helpful ? 'Positive feedback' : 'Negative feedback' }} for
          <span class="font-medium">{{ response?.page?.title ?? 'Unknown page' }}</span>
        </DialogDescription>
      </DialogHeader>

      <div v-if="response" class="space-y-4">
        <!-- Status Badge -->
        <div class="flex items-center gap-2">
          <Badge :variant="response.is_helpful ? 'default' : 'destructive'">
            {{ response.is_helpful ? 'Helpful' : 'Not Helpful' }}
          </Badge>
          <Badge v-if="formName" variant="outline">
            <FileText class="mr-1 h-3 w-3" />
            {{ formName }}
          </Badge>
          <Badge v-else variant="secondary">Default Form</Badge>
        </div>

        <Separator />

        <!-- Form Data / Comments -->
        <div v-if="formDataEntries.length > 0" class="space-y-3">
          <h4 class="text-sm font-medium">Response Data</h4>
          <div class="space-y-3 rounded-lg border bg-muted/50 p-4">
            <div v-for="entry in formDataEntries" :key="entry.label" class="space-y-1">
              <p class="text-xs font-medium text-muted-foreground">{{ entry.label }}</p>
              <p class="text-sm whitespace-pre-wrap">{{ entry.value || '—' }}</p>
            </div>
          </div>
        </div>
        <div
          v-else
          class="rounded-lg border bg-muted/50 p-4 text-center text-sm text-muted-foreground"
        >
          No additional comments provided
        </div>

        <Separator />

        <!-- Metadata -->
        <div class="space-y-3">
          <h4 class="text-sm font-medium">Metadata</h4>
          <div class="grid gap-3 text-sm">
            <div class="flex items-center gap-2 text-muted-foreground">
              <Globe class="h-4 w-4 shrink-0" />
              <span class="truncate">{{ response.page?.title ?? 'Unknown page' }}</span>
            </div>
            <div class="flex items-center gap-2 text-muted-foreground">
              <Calendar class="h-4 w-4 shrink-0" />
              <span>{{ formatDate(response.created_at) }}</span>
            </div>
            <div v-if="response.ip_address" class="flex items-center gap-2 text-muted-foreground">
              <Monitor class="h-4 w-4 shrink-0" />
              <span>{{ response.ip_address }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <Button variant="outline" @click="close">
          <X class="mr-2 h-4 w-4" />
          Close
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
