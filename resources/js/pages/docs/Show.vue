<script setup lang="ts">
import DocsBreadcrumb from '@/components/docs/DocsBreadcrumb.vue';
import DocsContent from '@/components/docs/DocsContent.vue';
import type { SidebarItem } from '@/components/docs/DocsNavigation.vue';
import DocsTableOfContents from '@/components/docs/DocsTableOfContents.vue';
import FeedbackWidget from '@/components/docs/FeedbackWidget.vue';
import { useCspNonce } from '@/composables/useCspNonce';
import DocsLayout from '@/layouts/DocsLayout.vue';
import type { SiteSettings } from '@/types';
import type { FeedbackForm } from '@/types/feedback';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const cspNonce = useCspNonce();

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);

interface NavigationTab {
  id: number;
  title: string;
  slug: string;
  icon: string | null;
  is_default: boolean;
}

interface CurrentPage {
  id: number;
  title: string;
  slug: string;
  content: string | null;
  content_raw: string | null;
  type: 'navigation' | 'group' | 'document';
  seo_title: string | null;
  seo_description: string | null;
  updated_at: string;
  source?: 'git' | 'cms';
  updated_at_git?: string | null;
  git_last_author?: string | null;
  edit_on_github_url?: string | null;
}

interface TocItem {
  id: string;
  text: string;
  level: number;
}

interface BreadcrumbItem {
  title: string;
  path: string;
  type: 'navigation' | 'group' | 'document';
}

interface Props {
  navigationTabs: NavigationTab[];
  activeNavId: number | null;
  sidebarItems: SidebarItem[];
  currentPage: CurrentPage | null;
  tableOfContents: TocItem[];
  breadcrumbs: BreadcrumbItem[];
  feedbackForms: FeedbackForm[];
}

const props = defineProps<Props>();

const pageTitle = computed(() => {
  if (!props.currentPage) {
    return 'Documentation';
  }
  return props.currentPage.seo_title || props.currentPage.title;
});

const pageDescription = computed(() => {
  return props.currentPage?.seo_description || '';
});

const currentPath = computed(() => {
  if (!props.currentPage) {
    return '';
  }

  if (!props.breadcrumbs.length) {
    return props.currentPage.slug;
  }

  return props.breadcrumbs[props.breadcrumbs.length - 1].path;
});

const lastUpdatedAt = computed(() => {
  if (!props.currentPage) {
    return undefined;
  }

  if (props.currentPage.source === 'git') {
    return props.currentPage.updated_at_git ?? props.currentPage.updated_at;
  }

  return props.currentPage.updated_at;
});

const showBreadcrumbs = computed(() => siteSettings.value?.layout?.showBreadcrumbs ?? true);
const showToc = computed(() => {
  return siteSettings.value?.layout?.showToc ?? true;
});
const tocPosition = computed(() => siteSettings.value?.layout?.tocPosition ?? 'right');

//inject style with csp on head
onMounted(() => {
  if (!siteSettings.value?.theme?.customCss) {
    return;
  }
  const style = document.createElement('style');
  style.setAttribute('nonce', cspNonce ?? '');
  style.textContent = siteSettings.value?.theme?.customCss ?? '';
  document.head.appendChild(style);
});
</script>

<template>
  <Head>
    <title>{{ pageTitle }}</title>
    <meta v-if="pageDescription" name="description" :content="pageDescription" />
    <link v-if="siteSettings?.favicon" rel="icon" :href="siteSettings.favicon" />
    <meta
      v-if="siteSettings?.advanced?.metaRobots"
      name="robots"
      :content="siteSettings.advanced.metaRobots"
    />
  </Head>

  <DocsLayout
    :navigation-tabs="navigationTabs"
    :active-nav-id="activeNavId"
    :sidebar-items="sidebarItems"
    :current-path="currentPath"
  >
    <div class="flex flex-col lg:flex-row">
      <DocsTableOfContents
        v-if="showToc && tocPosition === 'left' && currentPage?.type === 'document'"
        :items="tableOfContents"
        class="order-first"
      />

      <div class="docs-content-area flex-1 px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
        <DocsBreadcrumb v-if="showBreadcrumbs" :items="breadcrumbs" />

        <div v-if="currentPage && currentPage.type === 'document'">
          <DocsContent
            :content="currentPage.content ?? ''"
            :content-raw="currentPage.content_raw ?? ''"
            :title="currentPage.title"
            :updated-at="lastUpdatedAt"
            :git-last-author="currentPage.git_last_author ?? undefined"
            :edit-on-github-url="currentPage.edit_on_github_url ?? undefined"
          />
          <FeedbackWidget :page-id="currentPage.id" :forms="feedbackForms" />
        </div>

        <div v-else-if="currentPage && currentPage.type === 'group'" class="space-y-4">
          <h1 class="text-3xl font-bold">{{ currentPage.title }}</h1>
          <p class="text-muted-foreground">Select a page from the sidebar to view its content.</p>
        </div>

        <div v-else class="space-y-4">
          <h1 class="text-3xl font-bold">Welcome to Documentation</h1>
          <p class="text-muted-foreground">Select a page from the sidebar to get started.</p>
        </div>
      </div>

      <DocsTableOfContents
        v-if="showToc && tocPosition === 'right' && currentPage?.type === 'document'"
        :items="tableOfContents"
      />
    </div>
  </DocsLayout>
</template>
