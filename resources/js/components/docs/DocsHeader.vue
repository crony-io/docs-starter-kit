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
import { Link } from '@inertiajs/vue3';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

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
}

defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();
</script>

<template>
  <header
    class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
  >
    <div class="flex flex-1 items-center gap-2">
      <SidebarTrigger class="-ml-1" />
      <Separator orientation="vertical" class="mr-2 h-4" />

      <DocsNavigationTabs
        v-if="navigationTabs.length > 1"
        :tabs="navigationTabs"
        :active-id="activeNavId"
      />
    </div>

    <div class="flex items-center gap-2">
      <DropdownMenu>
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

      <Button v-if="$page.props.auth?.user" variant="ghost" size="sm" as-child>
        <Link :href="dashboard()">Admin</Link>
      </Button>
    </div>
  </header>
</template>
