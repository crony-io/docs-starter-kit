<script setup lang="ts">
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

interface NavigationTab {
  id: number;
  title: string;
  slug: string;
  icon: string | null;
  is_default: boolean;
}

interface Props {
  tabs: NavigationTab[];
  activeId: number | null;
}

defineProps<Props>();
</script>

<template>
  <nav class="flex items-center">
    <NavigationMenu>
      <NavigationMenuList>
        <NavigationMenuItem v-for="tab in tabs" :key="tab.id">
          <NavigationMenuLink as-child>
            <Link
              :href="`/docs/${tab.slug}`"
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
