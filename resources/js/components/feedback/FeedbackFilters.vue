<script setup lang="ts">
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { index as feedbackIndex } from '@/routes/admin/feedback';
import type { PageOption } from '@/types/feedback';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
  pages: PageOption[];
  filters: {
    page_id?: string;
    is_helpful?: string;
    start_date?: string;
    end_date?: string;
  };
}

const props = defineProps<Props>();

const pageFilter = ref(props.filters.page_id || 'all');
const helpfulFilter = ref(props.filters.is_helpful || 'all');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');

const applyFilters = () => {
  router.get(
    feedbackIndex().url,
    {
      page_id: pageFilter.value === 'all' ? undefined : pageFilter.value,
      is_helpful: helpfulFilter.value === 'all' ? undefined : helpfulFilter.value,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
    },
    { preserveState: true },
  );
};

watch([pageFilter, helpfulFilter, startDate, endDate], applyFilters);
</script>

<template>
  <div class="flex flex-wrap gap-4">
    <Select v-model="pageFilter">
      <SelectTrigger class="w-[200px]">
        <SelectValue placeholder="Filter by page" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">All Pages</SelectItem>
        <SelectItem v-for="page in pages" :key="page.id" :value="page.id.toString()">
          {{ page.title }}
        </SelectItem>
      </SelectContent>
    </Select>

    <Select v-model="helpfulFilter">
      <SelectTrigger class="w-[180px]">
        <SelectValue placeholder="Filter by type" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">All Responses</SelectItem>
        <SelectItem value="true">Helpful</SelectItem>
        <SelectItem value="false">Not Helpful</SelectItem>
      </SelectContent>
    </Select>

    <Input v-model="startDate" type="date" class="w-[160px]" />
    <Input v-model="endDate" type="date" class="w-[160px]" />
  </div>
</template>
