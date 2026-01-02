<script setup lang="ts">
import FeedbackDetailModal from '@/components/feedback/FeedbackDetailModal.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { destroy as destroyFeedback } from '@/routes/admin/feedback';
import type { PaginatedData } from '@/types';
import type { FeedbackResponse } from '@/types/feedback';
import { router } from '@inertiajs/vue3';
import { Eye, MessageSquare, ThumbsDown, ThumbsUp, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{ responses: PaginatedData<FeedbackResponse> }>();

const selectedResponse = ref<FeedbackResponse | null>(null);
const detailModalOpen = ref(false);

const openDetail = (response: FeedbackResponse) => {
  selectedResponse.value = response;
  detailModalOpen.value = true;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getFormDataPreview = (formData: FeedbackResponse['form_data']): string => {
  if (!formData?.responses) {
    return '';
  }
  // Get first text value from responses for preview
  const values = Object.values(formData.responses);
  for (const value of values) {
    if (typeof value === 'string' && value.trim()) {
      return value.length > 50 ? value.substring(0, 50) + '...' : value;
    }
  }
  return Object.keys(formData.responses).length > 0
    ? `${Object.keys(formData.responses).length} field(s)`
    : '';
};

const hasFormData = (formData: FeedbackResponse['form_data']): boolean => {
  if (!formData?.responses) {
    return false;
  }
  return Object.values(formData.responses).some((v) =>
    Array.isArray(v) ? v.length > 0 : v !== '' && v !== null,
  );
};

const deleteResponse = (id: number) => {
  if (confirm('Delete this feedback response?')) {
    router.delete(destroyFeedback(id).url);
  }
};
</script>

<template>
  <div class="rounded-lg border bg-card">
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Page</TableHead>
          <TableHead>Response</TableHead>
          <TableHead>Form / Comments</TableHead>
          <TableHead>Date</TableHead>
          <TableHead class="w-[100px]"></TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-if="responses.data.length === 0">
          <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
            No feedback responses found.
          </TableCell>
        </TableRow>
        <TableRow
          v-for="response in responses.data"
          :key="response.id"
          class="cursor-pointer hover:bg-muted/50"
          @click="openDetail(response)"
        >
          <TableCell>
            <span class="font-medium">{{ response.page?.title ?? 'Unknown' }}</span>
          </TableCell>
          <TableCell>
            <div class="flex items-center gap-2">
              <ThumbsUp v-if="response.is_helpful" class="h-4 w-4 text-green-500" />
              <ThumbsDown v-else class="h-4 w-4 text-red-500" />
              <span :class="response.is_helpful ? 'text-green-600' : 'text-red-600'">
                {{ response.is_helpful ? 'Helpful' : 'Not Helpful' }}
              </span>
            </div>
          </TableCell>
          <TableCell class="max-w-[300px]">
            <div class="flex items-center gap-2">
              <Badge v-if="response.form_data?.form_name" variant="outline" class="shrink-0">
                {{ response.form_data.form_name }}
              </Badge>
              <span
                v-if="hasFormData(response.form_data)"
                class="flex items-center gap-1 truncate text-muted-foreground"
              >
                <MessageSquare class="h-3 w-3 shrink-0" />
                {{ getFormDataPreview(response.form_data) }}
              </span>
              <span v-else class="text-muted-foreground">—</span>
            </div>
          </TableCell>
          <TableCell class="text-muted-foreground">
            {{ formatDate(response.created_at) }}
          </TableCell>
          <TableCell>
            <div class="flex items-center gap-1" @click.stop>
              <Button variant="ghost" size="icon" @click="openDetail(response)">
                <Eye class="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon" @click="deleteResponse(response.id)">
                <Trash2 class="h-4 w-4 text-destructive" />
              </Button>
            </div>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <Pagination
      :links="responses.links"
      :from="responses.from"
      :to="responses.to"
      :total="responses.total"
      item-label="responses"
    />

    <!-- Detail Modal -->
    <FeedbackDetailModal v-model:open="detailModalOpen" :response="selectedResponse" />
  </div>
</template>
