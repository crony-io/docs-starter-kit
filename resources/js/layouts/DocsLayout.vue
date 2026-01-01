<script setup lang="ts">
import DocsHeader from '@/components/docs/DocsHeader.vue';
import type { SidebarItem } from '@/components/docs/DocsNavigation.vue';
import DocsSidebar from '@/components/docs/DocsSidebar.vue';
import SearchDialog from '@/components/SearchDialog.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import type { SiteSettings } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, provide, ref } from 'vue';

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);
const isOpen = page.props.sidebarOpen;

const searchOpen = ref(false);

provide('searchOpen', searchOpen);

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

const navigationStyle = computed(() => siteSettings.value?.layout?.navigationStyle ?? 'sidebar');
const showSidebar = computed(
  () => navigationStyle.value === 'sidebar' || navigationStyle.value === 'both',
);
const showTopNav = computed(
  () => navigationStyle.value === 'topnav' || navigationStyle.value === 'both',
);

const cssVariables = computed(() => {
  const baseFontSize = siteSettings.value?.typography?.baseFontSize ?? 16;
  const headingScale = siteSettings.value?.typography?.headingScale ?? 1.25;

  return {
    '--docs-primary': siteSettings.value?.theme?.primaryColor ?? '#3B82F6',
    '--docs-secondary': siteSettings.value?.theme?.secondaryColor ?? '#6366F1',
    '--docs-accent': siteSettings.value?.theme?.accentColor ?? '#F59E0B',
    '--docs-bg': siteSettings.value?.theme?.backgroundColor ?? '#FFFFFF',
    '--docs-text': siteSettings.value?.theme?.textColor ?? '#1F2937',
    '--docs-sidebar-width': `${siteSettings.value?.layout?.sidebarWidth ?? 280}px`,
    '--docs-content-width': `${siteSettings.value?.layout?.contentWidth ?? 900}px`,
    '--docs-font-body': siteSettings.value?.typography?.bodyFont ?? 'Inter',
    '--docs-font-heading': siteSettings.value?.typography?.headingFont ?? 'Inter',
    '--docs-font-code': siteSettings.value?.typography?.codeFont ?? 'JetBrains Mono',
    '--docs-font-size': `${baseFontSize}px`,
    '--docs-line-height': siteSettings.value?.typography?.lineHeight ?? 1.6,
    '--docs-paragraph-spacing': `${siteSettings.value?.typography?.paragraphSpacing ?? 1.5}em`,
    '--docs-h1-size': `${Math.round(baseFontSize * Math.pow(headingScale, 4))}px`,
    '--docs-h2-size': `${Math.round(baseFontSize * Math.pow(headingScale, 3))}px`,
    '--docs-h3-size': `${Math.round(baseFontSize * Math.pow(headingScale, 2))}px`,
    '--docs-h4-size': `${Math.round(baseFontSize * headingScale)}px`,
  };
});
</script>

<template>
  <div class="docs-layout min-h-screen bg-background" v-csp-style="cssVariables">
    <SidebarProvider :default-open="isOpen">
      <DocsSidebar v-if="showSidebar" :items="sidebarItems" :current-path="currentPath" />
      <SidebarInset :class="{ 'no-sidebar': !showSidebar }">
        <DocsHeader
          :navigation-tabs="navigationTabs"
          :active-nav-id="activeNavId"
          :show-nav-tabs="showTopNav"
        />
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

    <SearchDialog v-model:open="searchOpen" />
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

.docs-layout h1 {
  font-size: var(--docs-h1-size);
}

.docs-layout h2 {
  font-size: var(--docs-h2-size);
}

.docs-layout h3 {
  font-size: var(--docs-h3-size);
}

.docs-layout h4 {
  font-size: var(--docs-h4-size);
}

.docs-layout pre,
.docs-layout code {
  font-family: var(--docs-font-code), monospace;
}

.docs-layout p {
  margin-bottom: var(--docs-paragraph-spacing);
}

.docs-main {
  max-width: var(--docs-content-width);
}

.docs-content-area {
  max-width: var(--docs-content-width);
}

.no-sidebar {
  margin-left: 0 !important;
}

@media (max-width: 640px) {
  .docs-content-area {
    max-width: 100%;
  }
}
</style>
