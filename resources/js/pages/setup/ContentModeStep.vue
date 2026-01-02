<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ContentMode } from '@/pages/setup/types';
import { ArrowLeft, Check, Edit3, GitBranch, Sparkles } from 'lucide-vue-next';

defineProps<{
  modelValue: ContentMode | null;
  showBackButton?: boolean;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: ContentMode];
  select: [mode: ContentMode];
  back: [];
}>();

const selectMode = (mode: ContentMode) => {
  emit('update:modelValue', mode);
  emit('select', mode);
};

const gitFeatures = [
  'Automatic sync from GitHub',
  'Version control with Git',
  'Pull request workflow',
  'Webhook support',
];

const cmsFeatures = [
  'Visual WYSIWYG editor',
  'Drag & drop organization',
  'Built-in file manager',
  'No Git knowledge needed',
];
</script>

<template>
  <div class="w-full max-w-3xl space-y-8">
    <div class="text-center">
      <div
        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10"
      >
        <Sparkles class="h-7 w-7 text-primary" />
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-foreground">Choose Content Mode</h1>
      <p class="mt-2 text-muted-foreground">How do you want to manage your documentation?</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <!-- Git Mode -->
      <button
        class="group relative flex flex-col overflow-hidden rounded-xl border-2 bg-card p-6 text-left transition-all duration-200 hover:shadow-xl"
        :class="
          modelValue === 'git'
            ? 'border-blue-500 ring-4 ring-blue-500/20'
            : 'border-border hover:border-blue-400'
        "
        @click="selectMode('git')"
      >
        <!-- Selected indicator -->
        <div
          v-if="modelValue === 'git'"
          class="absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-white"
        >
          <Check class="h-4 w-4" />
        </div>

        <div class="mb-4 flex items-center gap-3">
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl transition-colors"
            :class="
              modelValue === 'git'
                ? 'bg-blue-500 text-white'
                : 'bg-blue-100 text-blue-600 group-hover:bg-blue-500 group-hover:text-white dark:bg-blue-900/50 dark:text-blue-400'
            "
          >
            <GitBranch class="h-6 w-6" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-foreground">Git Mode</h3>
            <Badge variant="secondary" class="mt-1 text-xs">For Developers</Badge>
          </div>
        </div>

        <p class="mb-4 text-sm text-muted-foreground">
          Sync documentation from your GitHub repository. Perfect for developers and teams using
          version control.
        </p>

        <ul class="mt-auto space-y-2">
          <li
            v-for="feature in gitFeatures"
            :key="feature"
            class="flex items-center gap-2 text-sm text-muted-foreground"
          >
            <Check class="h-4 w-4 shrink-0 text-blue-500" />
            {{ feature }}
          </li>
        </ul>
      </button>

      <!-- CMS Mode -->
      <button
        class="group relative flex flex-col overflow-hidden rounded-xl border-2 bg-card p-6 text-left transition-all duration-200 hover:shadow-xl"
        :class="
          modelValue === 'cms'
            ? 'border-green-500 ring-4 ring-green-500/20'
            : 'border-border hover:border-green-400'
        "
        @click="selectMode('cms')"
      >
        <!-- Selected indicator -->
        <div
          v-if="modelValue === 'cms'"
          class="absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
        >
          <Check class="h-4 w-4" />
        </div>

        <div class="mb-4 flex items-center gap-3">
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl transition-colors"
            :class="
              modelValue === 'cms'
                ? 'bg-green-500 text-white'
                : 'bg-green-100 text-green-600 group-hover:bg-green-500 group-hover:text-white dark:bg-green-900/50 dark:text-green-400'
            "
          >
            <Edit3 class="h-6 w-6" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-foreground">CMS Mode</h3>
            <Badge variant="secondary" class="mt-1 text-xs">For Everyone</Badge>
          </div>
        </div>

        <p class="mb-4 text-sm text-muted-foreground">
          Visual editor with full admin panel. Perfect for non-technical teams and content writers.
        </p>

        <ul class="mt-auto space-y-2">
          <li
            v-for="feature in cmsFeatures"
            :key="feature"
            class="flex items-center gap-2 text-sm text-muted-foreground"
          >
            <Check class="h-4 w-4 shrink-0 text-green-500" />
            {{ feature }}
          </li>
        </ul>
      </button>
    </div>

    <div v-if="showBackButton" class="flex justify-center pt-2">
      <Button variant="ghost" size="sm" class="text-muted-foreground" @click="$emit('back')">
        <ArrowLeft class="mr-2 h-4 w-4" />
        Back to account setup
      </Button>
    </div>
  </div>
</template>
