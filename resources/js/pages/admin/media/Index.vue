<script setup lang="ts">
import FileManagerBrowser from '@/components/FileManager/FileManagerBrowser.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as mediaIndex } from '@/routes/admin/media';
import type { MediaFile } from '@/types/media';
import { Head } from '@inertiajs/vue3';

interface Props {
  files: {
    data: MediaFile[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters: {
    collection?: string;
    search?: string;
    type?: string;
  };
}

defineProps<Props>();

const breadcrumbs = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Media Library', href: mediaIndex().url },
];
</script>

<template>
  <Head title="Media Library" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col">
      <div class="border-b px-6 py-4">
        <Heading title="Media Library" description="Manage your uploaded files and images" />
      </div>

      <div class="flex-1 overflow-hidden">
        <FileManagerBrowser selection-mode="multiple" />
      </div>
    </div>
  </AppLayout>
</template>
