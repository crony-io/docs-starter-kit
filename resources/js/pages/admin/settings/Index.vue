<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
  advanced,
  branding,
  layout,
  index as settingsIndex,
  theme,
  typography,
} from '@/routes/admin/settings';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Code, Layout, Palette, Settings2, Type } from 'lucide-vue-next';

interface SettingGroup {
  key: string;
  label: string;
  description: string;
}

interface Props {
  settings: Record<string, unknown>;
  groups: SettingGroup[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Site Settings', href: settingsIndex().url },
];

const groupRoutes: Record<string, () => { url: string }> = {
  theme,
  typography,
  layout,
  branding,
  advanced,
};

const groupIcons: Record<string, typeof Palette> = {
  theme: Palette,
  typography: Type,
  layout: Layout,
  branding: Settings2,
  advanced: Code,
};

const groupColors: Record<string, string> = {
  theme: 'bg-violet-500/10 text-violet-600',
  typography: 'bg-blue-500/10 text-blue-600',
  layout: 'bg-emerald-500/10 text-emerald-600',
  branding: 'bg-amber-500/10 text-amber-600',
  advanced: 'bg-slate-500/10 text-slate-600',
};
</script>

<template>
  <Head title="Site Settings" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <Heading
        title="Site Settings"
        description="Customize your documentation site's appearance and behavior"
      />

      <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          v-for="group in groups"
          :key="group.key"
          :href="groupRoutes[group.key]().url"
          class="group"
        >
          <Card class="h-full transition-all hover:border-primary hover:shadow-md">
            <CardHeader class="pb-3">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-lg"
                  :class="groupColors[group.key]"
                >
                  <component :is="groupIcons[group.key]" class="h-5 w-5" />
                </div>
                <div class="flex-1">
                  <CardTitle class="text-lg">{{ group.label }}</CardTitle>
                </div>
                <ChevronRight
                  class="h-5 w-5 text-muted-foreground transition-transform group-hover:translate-x-1"
                />
              </div>
            </CardHeader>
            <CardContent>
              <CardDescription>{{ group.description }}</CardDescription>
            </CardContent>
          </Card>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
