<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import GithubIcon from '@/components/icons/GithubIcon.vue';
import type { SiteSettings } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CheckIcon, CopyIcon } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

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
  if (!contentRef.value || !showCodeLineNumbers.value) {
    return;
  }

  const codeBlocks = contentRef.value.querySelectorAll('pre code');
  codeBlocks.forEach((code) => {
    if (code.classList.contains('line-numbers-added')) {
      return;
    }
    code.classList.add('line-numbers-added');
    const lines = code.textContent?.split('\n') || [];
    if (lines.length > 1 && lines[lines.length - 1] === '') {
      lines.pop();
    }
    code.setAttribute('style', `counter-reset: line ${lines.length > 1 ? '' : 'none'}`);
  });
};

onMounted(() => {
  nextTick(() => {
    addCopyButtons();
    addLineNumbers();
  });
});

watch(
  () => props.content,
  () => {
    nextTick(() => {
      addCopyButtons();
      addLineNumbers();
    });
  },
);
</script>

<template>
  <article class="prose prose-slate dark:prose-invert max-w-none">
    <header v-if="title" class="mb-8 space-y-2">
      <div class="flex items-start justify-between gap-4">
        <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
        <div class="flex shrink-0 items-center gap-2">
          <Button v-if="hasEditLink" variant="outline" size="sm" as-child>
            <a :href="editOnGithubUrl" target="_blank" rel="noopener noreferrer">
              <GithubIcon class="mr-2 h-4 w-4" />
              Edit on GitHub
            </a>
          </Button>

          <Button variant="outline" size="sm" @click="copyPageContent">
            <CheckIcon v-if="copiedPage" class="mr-2 h-4 w-4 text-green-500" />
            <CopyIcon v-else class="mr-2 h-4 w-4" />
            {{ copiedPage ? 'Copied!' : 'Copy page' }}
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

    <div ref="contentRef" v-html="content" class="prose prose-slate dark:prose-invert max-w-none" />
  </article>
</template>
