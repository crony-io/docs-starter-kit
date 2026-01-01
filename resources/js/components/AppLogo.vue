<script setup lang="ts">
import type { SiteSettings } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';

defineOptions({
  inheritAttrs: false,
});

interface Props {
  className?: HTMLAttributes['class'];
  variant?: 'auto' | 'light' | 'dark';
}

withDefaults(defineProps<Props>(), {
  variant: 'auto',
});

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as SiteSettings | undefined);

const siteName = computed(() => siteSettings.value?.siteName ?? 'Docs');
const siteTagline = computed(() => siteSettings.value?.siteTagline ?? '');
const logoLight = computed(() => siteSettings.value?.logoLight);
const logoDark = computed(() => siteSettings.value?.logoDark);
const hasCustomLogo = computed(() => logoLight.value || logoDark.value);
</script>

<template>
  <template v-if="hasCustomLogo">
    <template v-if="variant === 'auto'">
      <img
        v-if="logoLight"
        :src="logoLight"
        :alt="siteName"
        :class="className"
        class="dark:hidden"
        v-bind="$attrs"
      />
      <img
        v-if="logoDark"
        :src="logoDark"
        :alt="siteName"
        :class="className"
        class="hidden dark:block"
        v-bind="$attrs"
      />
      <img
        v-if="logoLight && !logoDark"
        :src="logoLight"
        :alt="siteName"
        :class="className"
        class="hidden dark:block"
        v-bind="$attrs"
      />
      <img
        v-if="!logoLight && logoDark"
        :src="logoDark"
        :alt="siteName"
        :class="className"
        class="dark:hidden"
        v-bind="$attrs"
      />
    </template>
    <img
      v-else-if="variant === 'light' && logoLight"
      :src="logoLight"
      :alt="siteName"
      :class="className"
      v-bind="$attrs"
    />
    <img
      v-else-if="variant === 'dark' && logoDark"
      :src="logoDark"
      :alt="siteName"
      :class="className"
      v-bind="$attrs"
    />
    <img
      v-else
      :src="logoLight ?? logoDark ?? ''"
      :alt="siteName"
      :class="className"
      v-bind="$attrs"
    />
  </template>
  <div v-else class="flex flex-col">
    <h1 :class="className" v-bind="$attrs">{{ siteName }}</h1>
    <span
      v-if="siteTagline"
      class="text-xs text-muted-foreground group-data-[collapsible=icon]:hidden"
    >
      {{ siteTagline }}
    </span>
  </div>
</template>
