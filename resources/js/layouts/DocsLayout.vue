<script setup lang="ts">
import DocsHeader from '@/components/docs/DocsHeader.vue';
import type { SidebarItem } from '@/components/docs/DocsNavigation.vue';
import DocsSidebar from '@/components/docs/DocsSidebar.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import type { SiteSettings } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);
const isOpen = page.props.sidebarOpen;

useKeyboardShortcuts([
  {
    key: 'k',
    ctrl: true,
    handler: () => {
      const searchInput = document.querySelector<HTMLInputElement>(
        '[placeholder="Search docs..."]',
      );
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    },
  },
]);

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
  sidebarItems: SidebarItem[];
  currentPath: string;
}

defineProps<Props>();

const cssVariables = computed(() => ({
  '--docs-primary': siteSettings.value?.theme?.primaryColor ?? '#3B82F6',
  '--docs-secondary': siteSettings.value?.theme?.secondaryColor ?? '#6366F1',
  '--docs-accent': siteSettings.value?.theme?.accentColor ?? '#F59E0B',
  '--docs-content-width': `${siteSettings.value?.layout?.contentWidth ?? 900}px`,
  '--docs-font-body': siteSettings.value?.typography?.bodyFont ?? 'Inter',
  '--docs-font-heading': siteSettings.value?.typography?.headingFont ?? 'Inter',
  '--docs-font-code': siteSettings.value?.typography?.codeFont ?? 'JetBrains Mono',
  '--docs-font-size': `${siteSettings.value?.typography?.baseFontSize ?? 16}px`,
  '--docs-line-height': siteSettings.value?.typography?.lineHeight ?? 1.6,
}));
</script>

<template>
  <div class="docs-layout min-h-screen bg-background" v-csp-style="cssVariables">
    <SidebarProvider :default-open="isOpen">
      <DocsSidebar :items="sidebarItems" :current-path="currentPath" />
      <SidebarInset>
        <DocsHeader :navigation-tabs="navigationTabs" :active-nav-id="activeNavId" />
        <main class="docs-main flex-1 overflow-x-hidden">
          <slot />
        </main>
        <footer
          v-if="siteSettings?.layout?.showFooter && siteSettings?.layout?.footerText"
          class="border-t bg-muted/30 px-4 py-6 text-center text-sm text-muted-foreground"
          v-html="siteSettings.layout.footerText"
        />
      </SidebarInset>
    </SidebarProvider>

    <Teleport to="head">
      <style v-if="siteSettings?.theme?.customCss">
        {{ siteSettings.theme.customCss }}
      </style>
    </Teleport>
  </div>
</template>

<style>
.docs-layout {
  font-family: var(--docs-font-body), system-ui, sans-serif;
  font-size: var(--docs-font-size);
  line-height: var(--docs-line-height);
}

.docs-layout h1,
.docs-layout h2,
.docs-layout h3,
.docs-layout h4,
.docs-layout h5,
.docs-layout h6 {
  font-family: var(--docs-font-heading), system-ui, sans-serif;
}

.docs-layout pre,
.docs-layout code {
  font-family: var(--docs-font-code), monospace;
}

.docs-main {
  max-width: var(--docs-content-width);
}

.docs-content-area {
  max-width: var(--docs-content-width);
}
</style>
