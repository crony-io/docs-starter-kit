<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronUp } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

interface SelectScrollUpButtonProps {
  class?: HTMLAttributes['class'];
}

const props = defineProps<SelectScrollUpButtonProps>();
</script>

<template>
  <div
    aria-hidden="true"
    :class="cn('flex cursor-default items-center justify-center py-1', props.class)"
  >
    <slot>
      <ChevronUp class="h-4 w-4" />
    </slot>
  </div>
</template>
