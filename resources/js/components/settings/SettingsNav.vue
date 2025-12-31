<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { advanced, branding, layout, theme, typography } from '@/routes/admin/settings';
import { Link, usePage } from '@inertiajs/vue3';
import { Code, Layout, Palette, Settings2, Type } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const tabs = [
  { label: 'Theme', href: theme().url, icon: Palette },
  { label: 'Typography', href: typography().url, icon: Type },
  { label: 'Layout', href: layout().url, icon: Layout },
  { label: 'Branding', href: branding().url, icon: Settings2 },
  { label: 'Advanced', href: advanced().url, icon: Code },
];

const isActive = (href: string) => {
  return currentUrl.value.startsWith(href.split('?')[0]);
};
</script>

<template>
  <div class="mb-6 flex flex-wrap gap-2 border-b pb-4">
    <Button
      v-for="tab in tabs"
      :key="tab.href"
      :variant="isActive(tab.href) ? 'default' : 'ghost'"
      size="sm"
      as-child
    >
      <Link :href="tab.href" class="flex items-center gap-2">
        <component :is="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </Link>
    </Button>
  </div>
</template>
