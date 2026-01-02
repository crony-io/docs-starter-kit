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
import { computed, onMounted, onUnmounted } from 'vue';

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
  created_at: string;
  updated_at: string;
  source?: 'git' | 'cms';
  updated_at_git?: string | null;
  git_last_author?: string | null;
  edit_on_github_url?: string | null;
  canonical_url?: string | null;
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
  isEmpty?: boolean;
}

const props = defineProps<Props>();

const isAuthenticated = computed(() => !!(page.props.auth as { user?: unknown } | undefined)?.user);

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

const canonicalUrl = computed(() => props.currentPage?.canonical_url ?? '');

const siteName = computed(() => siteSettings.value?.siteName ?? 'Documentation');

const ogType = computed(() => {
  return props.currentPage?.type === 'document' ? 'article' : 'website';
});

const datePublished = computed(() => {
  if (!props.currentPage) {
    return '';
  }
  return new Date(props.currentPage.created_at).toISOString();
});

const dateModified = computed(() => {
  if (!props.currentPage) {
    return '';
  }
  const date =
    props.currentPage.source === 'git' && props.currentPage.updated_at_git
      ? props.currentPage.updated_at_git
      : props.currentPage.updated_at;
  return new Date(date).toISOString();
});

const jsonLd = computed(() => {
  if (!props.currentPage || props.currentPage.type !== 'document') {
    return null;
  }

  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'TechArticle',
    headline: pageTitle.value,
    description: pageDescription.value || undefined,
    datePublished: datePublished.value,
    dateModified: dateModified.value,
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': canonicalUrl.value,
    },
    publisher: {
      '@type': 'Organization',
      name: siteName.value,
    },
  };

  if (props.currentPage.git_last_author) {
    schema['author'] = {
      '@type': 'Person',
      name: props.currentPage.git_last_author,
    };
  }

  return JSON.stringify(schema);
});

let customStyleElement: HTMLStyleElement | null = null;
let jsonLdScript: HTMLScriptElement | null = null;

onMounted(() => {
  // Inject custom CSS
  if (siteSettings.value?.theme?.customCss) {
    customStyleElement = document.createElement('style');
    customStyleElement.setAttribute('nonce', cspNonce ?? '');
    customStyleElement.textContent = siteSettings.value.theme.customCss;
    document.head.appendChild(customStyleElement);
  }

  // Inject JSON-LD structured data
  if (jsonLd.value) {
    jsonLdScript = document.createElement('script');
    jsonLdScript.type = 'application/ld+json';
    jsonLdScript.setAttribute('nonce', cspNonce ?? '');
    jsonLdScript.textContent = jsonLd.value;
    document.head.appendChild(jsonLdScript);
  }
});

onUnmounted(() => {
  // Clean up custom CSS on page navigation
  if (customStyleElement?.parentNode) {
    customStyleElement.parentNode.removeChild(customStyleElement);
  }

  // Clean up JSON-LD script on page navigation
  if (jsonLdScript?.parentNode) {
    jsonLdScript.parentNode.removeChild(jsonLdScript);
  }
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

    <!-- Canonical URL -->
    <link v-if="canonicalUrl" rel="canonical" :href="canonicalUrl" />

    <!-- OpenGraph Tags -->
    <meta property="og:type" :content="ogType" />
    <meta property="og:title" :content="pageTitle" />
    <meta v-if="pageDescription" property="og:description" :content="pageDescription" />
    <meta v-if="canonicalUrl" property="og:url" :content="canonicalUrl" />
    <meta property="og:site_name" :content="siteName" />
    <meta
      v-if="currentPage?.type === 'document'"
      property="article:published_time"
      :content="datePublished"
    />
    <meta
      v-if="currentPage?.type === 'document'"
      property="article:modified_time"
      :content="dateModified"
    />
    <meta
      v-if="currentPage?.git_last_author"
      property="article:author"
      :content="currentPage.git_last_author"
    />

    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" :content="pageTitle" />
    <meta v-if="pageDescription" name="twitter:description" :content="pageDescription" />
    <meta
      v-if="siteSettings?.social?.twitter"
      name="twitter:site"
      :content="`@${siteSettings.social.twitter.replace(/^@/, '')}`"
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

        <div
          v-else-if="isEmpty"
          class="flex min-h-[60vh] flex-col items-center justify-center text-center"
        >
          <div class="mx-auto max-w-md space-y-6">
            <div
              class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary/10"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-10 w-10 text-primary"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-foreground">No Documentation Yet</h1>
              <p class="mt-2 text-muted-foreground">
                Your documentation site is ready! Create your first page to get started.
              </p>
            </div>
            <a
              v-if="isAuthenticated"
              href="/admin/pages/create"
              class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 font-medium text-primary-foreground transition-colors hover:bg-primary/90"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                />
              </svg>
              Create First Page
            </a>
            <a
              v-else
              href="/login"
              class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 font-medium text-primary-foreground transition-colors hover:bg-primary/90"
            >
              Login to Get Started
            </a>
          </div>
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
