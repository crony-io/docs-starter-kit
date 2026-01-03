<script setup lang="ts">
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { show } from '@/routes/docs';
import type { NavigationTab } from '@/types/docs';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
  tabs: NavigationTab[];
  activeId: number | null;
}

const props = defineProps<Props>();

const activeTab = computed(() => props.tabs.find((tab) => tab.id === props.activeId));

const handleTabChange = (slug: string) => {
  router.visit(show.url(slug));
};
</script>

<template>
  <!-- Mobile: Select dropdown -->
  <div class="sm:hidden">
    <Select :model-value="activeTab?.slug" @update:model-value="handleTabChange">
      <SelectTrigger class="h-8 w-[140px] text-sm">
        <SelectValue :placeholder="activeTab?.title || 'Select section'" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="tab in tabs" :key="tab.id" :value="tab.slug">
          {{ tab.title }}
        </SelectItem>
      </SelectContent>
    </Select>
  </div>

  <!-- Desktop: Horizontal tabs -->
  <nav class="hidden items-center sm:flex">
    <NavigationMenu>
      <NavigationMenuList>
        <NavigationMenuItem v-for="tab in tabs" :key="tab.id">
          <NavigationMenuLink as-child>
            <Link
              :href="show.url(tab.slug)"
              :class="
                cn(
                  navigationMenuTriggerStyle(),
                  'h-8 rounded-md px-3 text-sm',
                  tab.id === activeId && 'bg-accent text-accent-foreground',
                )
              "
            >
              {{ tab.title }}
            </Link>
          </NavigationMenuLink>
        </NavigationMenuItem>
      </NavigationMenuList>
    </NavigationMenu>
  </nav>
</template>
