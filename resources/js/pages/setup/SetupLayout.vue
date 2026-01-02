<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { useAppearance } from '@/composables/useAppearance';

defineProps<{
  title?: string;
  description?: string;
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '4xl';
}>();

const { appearance } = useAppearance();
</script>

<template>
  <div class="relative flex min-h-svh flex-col items-center justify-center bg-muted p-4 md:p-8">
    <!-- Background pattern -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div
        class="absolute -top-1/2 left-1/2 h-[800px] w-[800px] -translate-x-1/2 rounded-full opacity-20 blur-3xl"
        :class="appearance === 'dark' ? 'bg-primary/30' : 'bg-primary/10'"
      />
    </div>

    <div
      class="relative z-10 flex w-full flex-col items-center gap-8"
      :class="{
        'max-w-sm': maxWidth === 'sm',
        'max-w-md': maxWidth === 'md' || !maxWidth,
        'max-w-lg': maxWidth === 'lg',
        'max-w-xl': maxWidth === 'xl',
        'max-w-2xl': maxWidth === '2xl',
        'max-w-4xl': maxWidth === '4xl',
      }"
    >
      <!-- Logo -->
      <div class="flex items-center justify-center">
        <div class="flex h-auto w-[200px] items-center justify-center">
          <AppLogo class="fill-current text-foreground" />
        </div>
      </div>

      <!-- Title & Description -->
      <div v-if="title || description" class="text-center">
        <h1 v-if="title" class="text-2xl font-bold tracking-tight text-foreground">{{ title }}</h1>
        <p v-if="description" class="mt-2 text-muted-foreground">{{ description }}</p>
      </div>

      <!-- Content -->
      <slot />
    </div>
  </div>
</template>
