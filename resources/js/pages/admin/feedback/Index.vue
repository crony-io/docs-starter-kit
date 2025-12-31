<script setup lang="ts">
import FeedbackFilters from '@/components/feedback/FeedbackFilters.vue';
import FeedbackPageStats from '@/components/feedback/FeedbackPageStats.vue';
import FeedbackStatsCards from '@/components/feedback/FeedbackStatsCards.vue';
import FeedbackTable from '@/components/feedback/FeedbackTable.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
  exportMethod as feedbackExport,
  forms as feedbackForms,
  index as feedbackIndex,
} from '@/routes/admin/feedback';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import type { FeedbackResponse, FeedbackStats, PageOption, PageStat } from '@/types/feedback';
import { Head, Link } from '@inertiajs/vue3';
import { Download, FileText } from 'lucide-vue-next';

interface Props {
  responses: PaginatedData<FeedbackResponse>;
  stats: FeedbackStats;
  pageStats: PageStat[];
  pages: PageOption[];
  filters: {
    page_id?: string;
    is_helpful?: string;
    start_date?: string;
    end_date?: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Feedback', href: feedbackIndex().url },
];

const exportData = () => {
  const params = new URLSearchParams();
  if (props.filters.page_id) {
    params.set('page_id', props.filters.page_id);
  }
  if (props.filters.is_helpful) {
    params.set('is_helpful', props.filters.is_helpful);
  }
  if (props.filters.start_date) {
    params.set('start_date', props.filters.start_date);
  }
  if (props.filters.end_date) {
    params.set('end_date', props.filters.end_date);
  }
  params.set('format', 'csv');
  window.location.href = feedbackExport.url({ query: Object.fromEntries(params) });
};
</script>

<template>
  <Head title="Feedback Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex items-center justify-between">
        <Heading title="Feedback" description="View and manage user feedback" />
        <div class="flex gap-2">
          <Button variant="outline" as-child>
            <Link :href="feedbackForms()">
              <FileText class="mr-2 h-4 w-4" />
              Manage Forms
            </Link>
          </Button>
          <Button variant="outline" @click="exportData">
            <Download class="mr-2 h-4 w-4" />
            Export CSV
          </Button>
        </div>
      </div>

      <FeedbackStatsCards :stats="stats" class="mt-6" />
      <FeedbackPageStats v-if="pageStats.length > 0" :page-stats="pageStats" class="mt-6" />
      <FeedbackFilters :pages="pages" :filters="filters" class="mt-6" />
      <FeedbackTable :responses="responses" class="mt-6" />
    </div>
  </AppLayout>
</template>
