<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface SelectSeparatorProps {
  class?: HTMLAttributes['class'];
}

const props = defineProps<SelectSeparatorProps>();
</script>

<template>
  <div
    role="separator"
    aria-orientation="horizontal"
    :class="cn('-mx-1 my-1 h-px bg-muted', props.class)"
  />
</template>
