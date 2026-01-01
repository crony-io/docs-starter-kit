<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import type { SidebarItem } from '@/components/docs/DocsNavigation.vue';
import DocsNavigationItem from '@/components/docs/DocsNavigationItem.vue';
import DiscordIcon from '@/components/icons/DiscordIcon.vue';
import GithubIcon from '@/components/icons/GithubIcon.vue';
import LinkedInIcon from '@/components/icons/LinkedInIcon.vue';
import TwitterIcon from '@/components/icons/TwitterIcon.vue';
import { Button } from '@/components/ui/button';
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
import { Download, Search } from 'lucide-vue-next';
import { computed, inject, type Ref } from 'vue';

interface Props {
  items: SidebarItem[];
  currentPath: string;
}

defineProps<Props>();

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);
const searchEnabled = computed(() => siteSettings.value?.advanced?.searchEnabled ?? true);

const searchOpen = inject<Ref<boolean>>('searchOpen');

function openSearch() {
  if (searchOpen) {
    searchOpen.value = true;
  }
}
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
        <div v-if="searchEnabled" class="px-1 pb-3 group-data-[collapsible=icon]:hidden">
          <Button
            variant="outline"
            class="w-full justify-start gap-2 text-muted-foreground"
            @click="openSearch"
          >
            <Search class="h-4 w-4" />
            <span class="flex-1 text-left">Search docs...</span>
            <kbd class="rounded border bg-muted px-1.5 py-0.5 font-mono text-[10px]"> Ctrl+K </kbd>
          </Button>
        </div>
        <SidebarGroupContent>
          <SidebarMenu>
            <DocsNavigationItem
              v-for="item in items"
              :key="item.id"
              :item="item"
              :current-path="currentPath"
              :depth="0"
            />
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
        <SidebarMenuItem v-if="siteSettings?.social?.twitter">
          <SidebarMenuButton as-child :tooltip="'Twitter / X'">
            <a :href="siteSettings.social.twitter" target="_blank" rel="noopener noreferrer">
              <TwitterIcon class="h-4 w-4" />
              <span>Twitter / X</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem v-if="siteSettings?.social?.discord">
          <SidebarMenuButton as-child :tooltip="'Discord'">
            <a :href="siteSettings.social.discord" target="_blank" rel="noopener noreferrer">
              <DiscordIcon class="h-4 w-4" />
              <span>Discord</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem v-if="siteSettings?.social?.linkedin">
          <SidebarMenuButton as-child :tooltip="'LinkedIn'">
            <a :href="siteSettings.social.linkedin" target="_blank" rel="noopener noreferrer">
              <LinkedInIcon class="h-4 w-4" />
              <span>LinkedIn</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarFooter>
  </Sidebar>
</template>
