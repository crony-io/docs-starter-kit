<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit } from '@/routes/admin/pages';
import type { Page } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Book, FileText, FolderTree } from 'lucide-vue-next';

defineProps<{ pages: Page[] }>();

const typeIcons = {
  navigation: Book,
  group: FolderTree,
  document: FileText,
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Recently Updated</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="pages.length === 0" class="py-4 text-center text-sm text-muted-foreground">
        No pages yet.
      </div>
      <div v-else class="space-y-3">
        <Link
          v-for="page in pages"
          :key="page.id"
          :href="edit(page.id)"
          class="flex items-center gap-3 rounded-md p-2 hover:bg-accent"
        >
          <component :is="typeIcons[page.type]" class="h-4 w-4 text-muted-foreground" />
          <div class="flex-1 truncate">
            <p class="truncate text-sm font-medium">{{ page.title }}</p>
            <p class="text-xs text-muted-foreground">{{ formatDate(page.updated_at) }}</p>
          </div>
          <StatusBadge :status="page.status" />
        </Link>
      </div>
    </CardContent>
  </Card>
</template>
