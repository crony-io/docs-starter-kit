<script setup lang="ts">
import GithubIcon from '@/components/icons/GithubIcon.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type { SiteSettings } from '@/types';
import { usePage } from '@inertiajs/vue3';
import hljs from 'highlight.js';
import { CheckIcon, CopyIcon, DownloadIcon } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

// Register language aliases for common file types not natively supported
hljs.registerAliases(['env', 'dotenv'], { languageName: 'ini' });
hljs.registerAliases(['conf', 'config'], { languageName: 'ini' });
hljs.registerAliases(['txt', 'text'], { languageName: 'plaintext' });

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);
const showCodeCopyButton = computed(() => siteSettings.value?.advanced?.codeCopyButton ?? true);
const showCodeLineNumbers = computed(() => siteSettings.value?.advanced?.codeLineNumbers ?? true);

interface Props {
  content: string;
  contentRaw?: string;
  title?: string;
  updatedAt?: string;
  editOnGithubUrl?: string;
  gitLastAuthor?: string;
}

const props = defineProps<Props>();

const contentRef = ref<HTMLElement>();
const copiedPage = ref(false);

const formattedDate = computed(() => {
  if (!props.updatedAt) {
    return null;
  }
  return new Date(props.updatedAt).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
});

const hasEditLink = computed(() => !!props.editOnGithubUrl);

const copyPageContent = async () => {
  const text = props.contentRaw || '';
  if (!text) {
    return;
  }

  try {
    await navigator.clipboard.writeText(text);
    copiedPage.value = true;
    setTimeout(() => {
      copiedPage.value = false;
    }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
};

const downloadPageContent = () => {
  const text = props.contentRaw || '';
  if (!text || !props.title) {
    return;
  }

  const filename = `${props.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}.txt`;
  const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
  const url = URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
};

const addCopyButtons = () => {
  if (!contentRef.value) {
    return;
  }

  const codeBlocks = contentRef.value.querySelectorAll('pre');

  if (!showCodeCopyButton.value) {
    return;
  }

  codeBlocks.forEach((pre) => {
    if (pre.querySelector('.copy-button')) {
      return;
    }

    pre.classList.add('group', 'relative');

    const button = document.createElement('button');
    button.className =
      'copy-button absolute right-2 top-2 rounded-md bg-background/80 p-2 opacity-0 transition-opacity hover:bg-background group-hover:opacity-100';
    button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>`;

    button.addEventListener('click', async () => {
      const code = pre.querySelector('code')?.textContent ?? pre.textContent ?? '';
      try {
        await navigator.clipboard.writeText(code);
        button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500"><polyline points="20 6 9 17 4 12"/></svg>`;
        setTimeout(() => {
          button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>`;
        }, 2000);
      } catch (error) {
        console.error('Failed to copy:', error);
      }
    });

    pre.appendChild(button);
  });
};

const addLineNumbers = () => {
  if (!contentRef.value) {
    return;
  }

  const codeBlocks = contentRef.value.querySelectorAll('pre code');
  codeBlocks.forEach((code) => {
    if (code.classList.contains('line-numbers-processed')) {
      return;
    }
    code.classList.add('line-numbers-processed');

    if (!showCodeLineNumbers.value) {
      return;
    }

    code.classList.add('line-numbers-added');

    // Wrap each line in a span for CSS counter
    const html = code.innerHTML;
    const lines = html.split('\n');
    if (lines.length > 1 && lines[lines.length - 1] === '') {
      lines.pop();
    }

    const wrappedLines = lines.map((line) => `<span class="code-line">${line}</span>`).join('\n');
    code.innerHTML = wrappedLines;
  });
};

const slugify = (text: string): string => {
  return text
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_]+/g, '-')
    .toLowerCase()
    .replace(/^-+|-+$/g, '');
};

const addHeadingIds = () => {
  if (!contentRef.value) {
    return;
  }

  const idCounts = new Map<string, number>();
  const headings = contentRef.value.querySelectorAll('h1, h2, h3');

  headings.forEach((heading) => {
    if (heading.id) {
      return;
    }
    const text = heading.textContent || '';
    const baseId = slugify(text);

    // Track occurrences and append suffix for duplicates
    const count = idCounts.get(baseId) || 0;
    idCounts.set(baseId, count + 1);

    heading.id = count === 0 ? baseId : `${baseId}-${count}`;
  });
};

const applySyntaxHighlighting = () => {
  if (!contentRef.value) {
    return;
  }

  const codeBlocks = contentRef.value.querySelectorAll('pre code');
  codeBlocks.forEach((block) => {
    if (block.classList.contains('hljs')) {
      return;
    }

    // Get language from class (e.g., 'language-javascript' -> 'javascript')
    const langClass = Array.from(block.classList).find((c) => c.startsWith('language-'));
    const language = langClass?.replace('language-', '');

    // Check if language is supported, fallback to plaintext if not
    if (language && !hljs.getLanguage(language)) {
      block.classList.remove(langClass!);
      block.classList.add('language-plaintext');
    }

    hljs.highlightElement(block as HTMLElement);
  });
};

onMounted(() => {
  nextTick(() => {
    addHeadingIds();
    applySyntaxHighlighting();
    addCopyButtons();
    addLineNumbers();
  });
});

watch(
  () => props.content,
  () => {
    nextTick(() => {
      addHeadingIds();
      applySyntaxHighlighting();
      addCopyButtons();
      addLineNumbers();
    });
  },
);
</script>

<template>
  <article class="prose max-w-none prose-slate dark:prose-invert">
    <header v-if="title" class="mb-6 space-y-3 sm:mb-8 sm:space-y-2">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ title }}</h1>
        <div class="flex flex-wrap items-center gap-2">
          <Button
            v-if="hasEditLink"
            variant="outline"
            size="sm"
            class="h-8 px-2 text-xs sm:h-9 sm:px-3 sm:text-sm"
            as-child
          >
            <a :href="editOnGithubUrl" target="_blank" rel="noopener noreferrer">
              <GithubIcon class="h-4 w-4 sm:mr-2" />
              <span class="hidden sm:inline">Edit on GitHub</span>
            </a>
          </Button>

          <Button
            variant="outline"
            size="sm"
            class="h-8 px-2 text-xs sm:h-9 sm:px-3 sm:text-sm"
            @click="copyPageContent"
          >
            <CheckIcon v-if="copiedPage" class="h-4 w-4 text-green-500 sm:mr-2" />
            <CopyIcon v-else class="h-4 w-4 sm:mr-2" />
            <span class="hidden sm:inline">{{ copiedPage ? 'Copied!' : 'Copy page' }}</span>
          </Button>

          <Button
            variant="outline"
            size="sm"
            class="h-8 px-2 text-xs sm:h-9 sm:px-3 sm:text-sm"
            @click="downloadPageContent"
            title="Download as TXT"
          >
            <DownloadIcon class="h-4 w-4 sm:mr-2" />
            <span class="hidden sm:inline">Download</span>
          </Button>
        </div>
      </div>
      <p v-if="formattedDate" class="text-sm text-muted-foreground">
        Last updated: {{ formattedDate }}
      </p>
      <p v-if="gitLastAuthor" class="text-sm text-muted-foreground">
        Last commit author: {{ gitLastAuthor }}
      </p>
      <Separator class="mt-4" />
    </header>

    <div ref="contentRef" v-html="content" class="prose max-w-none prose-slate dark:prose-invert" />
  </article>
</template>
