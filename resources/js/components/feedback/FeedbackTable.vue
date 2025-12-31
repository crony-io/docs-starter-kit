<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
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
import { ThumbsDown, ThumbsUp, Trash2 } from 'lucide-vue-next';

defineProps<{ responses: PaginatedData<FeedbackResponse> }>();

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
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
          <TableHead>Comment</TableHead>
          <TableHead>Date</TableHead>
          <TableHead class="w-[70px]"></TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-if="responses.data.length === 0">
          <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
            No feedback responses found.
          </TableCell>
        </TableRow>
        <TableRow v-for="response in responses.data" :key="response.id">
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
          <TableCell class="max-w-[300px] truncate text-muted-foreground">
            {{ response.form_data?.comment ?? '—' }}
          </TableCell>
          <TableCell class="text-muted-foreground">
            {{ formatDate(response.created_at) }}
          </TableCell>
          <TableCell>
            <Button variant="ghost" size="icon" @click="deleteResponse(response.id)">
              <Trash2 class="h-4 w-4 text-destructive" />
            </Button>
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
  </div>
</template>
