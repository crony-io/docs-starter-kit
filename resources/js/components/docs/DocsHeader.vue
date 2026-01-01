<script setup lang="ts">
import DocsNavigationTabs from '@/components/docs/DocsNavigationTabs.vue';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard } from '@/routes';
import type { SiteSettings } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);
const darkModeSetting = computed(() => siteSettings.value?.theme?.darkMode ?? 'system');
const canToggleTheme = computed(() => darkModeSetting.value === 'system');

interface NavigationTab {
  id: number;
  title: string;
  slug: string;
  icon: string | null;
  is_default: boolean;
}

interface Props {
  navigationTabs: NavigationTab[];
  activeNavId: number | null;
  showNavTabs?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showNavTabs: true,
});

const { appearance, updateAppearance } = useAppearance();
</script>

<template>
  <header
    class="flex h-14 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-3 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:h-16 sm:px-4 md:px-6"
  >
    <div class="flex flex-1 items-center gap-2">
      <SidebarTrigger class="-ml-1" />
      <Separator orientation="vertical" class="mr-2 h-4" />

      <DocsNavigationTabs
        v-if="props.showNavTabs && navigationTabs.length > 1"
        :tabs="navigationTabs"
        :active-id="activeNavId"
      />
    </div>

    <div class="flex items-center gap-2">
      <DropdownMenu v-if="canToggleTheme">
        <DropdownMenuTrigger as-child>
          <Button variant="ghost" size="icon" class="h-9 w-9">
            <Sun v-if="appearance === 'light'" class="h-4 w-4" />
            <Moon v-else-if="appearance === 'dark'" class="h-4 w-4" />
            <Monitor v-else class="h-4 w-4" />
            <span class="sr-only">Toggle theme</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem @click="updateAppearance('light')">
            <Sun class="mr-2 h-4 w-4" />
            Light
          </DropdownMenuItem>
          <DropdownMenuItem @click="updateAppearance('dark')">
            <Moon class="mr-2 h-4 w-4" />
            Dark
          </DropdownMenuItem>
          <DropdownMenuItem @click="updateAppearance('system')">
            <Monitor class="mr-2 h-4 w-4" />
            System
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <div v-else class="flex items-center">
        <Sun v-if="darkModeSetting === 'light'" class="h-4 w-4 text-muted-foreground" />
        <Moon v-else class="h-4 w-4 text-muted-foreground" />
      </div>

      <Button v-if="$page.props.auth?.user" variant="ghost" size="sm" as-child>
        <Link :href="dashboard()">Admin</Link>
      </Button>
    </div>
  </header>
</template>
