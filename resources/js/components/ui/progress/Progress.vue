<script setup lang="ts">
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import type { ProgressRootProps } from 'reka-ui';
import { ProgressIndicator, ProgressRoot } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = withDefaults(defineProps<ProgressRootProps & { class?: HTMLAttributes['class'] }>(), {
  modelValue: 0,
});

const delegatedProps = reactiveOmit(props, 'class');

const progressOffset = computed(() => 100 - (props.modelValue ?? 0));
</script>

<template>
  <ProgressRoot
    v-bind="delegatedProps"
    :class="cn('relative h-4 w-full overflow-hidden rounded-full bg-secondary', props.class)"
  >
    <ProgressIndicator
      class="progress-indicator h-full w-full flex-1 bg-primary transition-all"
      v-csp-style="{ '--progress-offset': `${progressOffset}%` }"
    />
  </ProgressRoot>
</template>

<style>
.progress-indicator {
  transform: translateX(calc(-1 * var(--progress-offset)));
}
</style>
