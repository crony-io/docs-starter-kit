<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface SelectGroupProps {
  class?: HTMLAttributes['class'];
}

const props = defineProps<SelectGroupProps>();
</script>

<template>
  <div role="group" :class="cn('w-full p-1', props.class)">
    <slot />
  </div>
</template>
