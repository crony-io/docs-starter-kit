<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import type { SidebarItem } from '@/components/docs/DocsNavigation.vue';
import DocsNavigationItem from '@/components/docs/DocsNavigationItem.vue';
import GithubIcon from '@/components/icons/GithubIcon.vue';
import { Input } from '@/components/ui/input';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { SiteSettings } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Download, ExternalLink, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
  items: SidebarItem[];
  currentPath: string;
}

const props = defineProps<Props>();

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);

const searchQuery = ref('');

const filterItems = (items: SidebarItem[], query: string): SidebarItem[] => {
  if (!query.trim()) {
    return items;
  }

  const lowerQuery = query.toLowerCase();

  return items.reduce<SidebarItem[]>((acc, item) => {
    const titleMatches = item.title.toLowerCase().includes(lowerQuery);
    const filteredChildren = item.children ? filterItems(item.children, query) : [];

    if (titleMatches || filteredChildren.length > 0) {
      acc.push({
        ...item,
        isExpanded: filteredChildren.length > 0 ? true : item.isExpanded,
        children: filteredChildren.length > 0 ? filteredChildren : item.children,
      });
    }

    return acc;
  }, []);
};

const filteredItems = computed(() => filterItems(props.items, searchQuery.value));

const clearSearch = () => {
  searchQuery.value = '';
};
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton class="h-auto w-[228px] max-w-full" as-child>
            <Link href="/">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <SidebarGroup class="px-2 py-0">
        <div class="px-1 pb-3 group-data-[collapsible=icon]:hidden">
          <div class="relative">
            <Search class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              type="search"
              placeholder="Search docs..."
              class="pr-8 pl-8"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="absolute top-2.5 right-2.5 text-muted-foreground hover:text-foreground"
              @click="clearSearch"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>
        <SidebarGroupContent>
          <SidebarMenu>
            <template v-if="filteredItems.length > 0">
              <DocsNavigationItem
                v-for="item in filteredItems"
                :key="item.id"
                :item="item"
                :current-path="currentPath"
                :depth="0"
              />
            </template>
            <div
              v-else
              class="px-3 py-6 text-center text-sm text-muted-foreground group-data-[collapsible=icon]:hidden"
            >
              No results found
            </div>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <SidebarFooter>
      <SidebarMenu>
        <SidebarMenuItem v-if="siteSettings?.advanced?.llmTxtEnabled">
          <SidebarMenuButton as-child :tooltip="'Download Full Docs'">
            <a href="/storage/llms-full.txt" download="llms-full.txt">
              <Download class="h-4 w-4" />
              <span>Download Full Docs</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem v-if="siteSettings?.social?.github">
          <SidebarMenuButton as-child :tooltip="'GitHub'">
            <a :href="siteSettings.social.github" target="_blank" rel="noopener noreferrer">
              <GithubIcon class="h-4 w-4" />
              <span>GitHub</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem>
          <SidebarMenuButton as-child :tooltip="'View Docs'">
            <a href="https://github.com/crony-io/docs-starter-kit" target="_blank" rel="noopener">
              <ExternalLink class="h-4 w-4" />
              <span>Docs Starter Kit</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarFooter>
  </Sidebar>
</template>
