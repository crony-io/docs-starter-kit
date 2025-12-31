<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface SelectLabelProps {
  class?: HTMLAttributes['class'];
}

const props = defineProps<SelectLabelProps>();
</script>

<template>
  <div :class="cn('py-1.5 pr-2 pl-8 text-sm font-semibold', props.class)">
    <slot />
  </div>
</template>
