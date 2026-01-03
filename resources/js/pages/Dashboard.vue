<script setup lang="ts">
import DashboardStats from '@/components/dashboard/DashboardStats.vue';
import FeedbackChart from '@/components/dashboard/FeedbackChart.vue';
import RecentPages from '@/components/dashboard/RecentPages.vue';
import GitSyncStatusIndicator from '@/components/GitSyncStatusIndicator.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { forms as feedbackForms } from '@/routes/admin/feedback';
import { create as pagesCreate } from '@/routes/admin/pages';
import { theme as settingsTheme } from '@/routes/admin/settings';
import type { BreadcrumbItem } from '@/types';
import type { GitSyncStatus } from '@/types/git-sync';
import type { Page } from '@/types/pages';
import { Head, Link } from '@inertiajs/vue3';
import { FileText, FolderTree, MessageSquare, Plus, Settings } from 'lucide-vue-next';

interface Stats {
  totalPages: number;
  publishedPages: number;
  draftPages: number;
  totalFeedback: number;
  positiveFeedback: number;
}

defineProps<{
  stats: Stats;
  recentPages: Page[];
  gitSyncStatus: GitSyncStatus | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
];

const quickActions = [
  { label: 'New Page', href: pagesCreate.url({ query: { type: 'document' } }), icon: FileText },
  { label: 'New Group', href: pagesCreate.url({ query: { type: 'group' } }), icon: FolderTree },
  { label: 'Feedback Forms', href: feedbackForms.url(), icon: MessageSquare },
  { label: 'Site Settings', href: settingsTheme.url(), icon: Settings },
];
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <h1 class="text-2xl font-bold">Dashboard</h1>
          <GitSyncStatusIndicator :git-sync-status="gitSyncStatus" />
        </div>
        <Button as-child>
          <Link :href="pagesCreate()">
            <Plus class="mr-2 h-4 w-4" />
            New Page
          </Link>
        </Button>
      </div>

      <DashboardStats :stats="stats" />

      <Card class="mt-6">
        <CardHeader class="pb-3">
          <CardTitle class="text-base">Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="action in quickActions"
              :key="action.href"
              variant="outline"
              size="sm"
              as-child
            >
              <Link :href="action.href">
                <component :is="action.icon" class="mr-2 h-4 w-4" />
                {{ action.label }}
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>

      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <FeedbackChart :stats="stats" />
        <RecentPages :pages="recentPages" />
      </div>
    </div>
  </AppLayout>
</template>
