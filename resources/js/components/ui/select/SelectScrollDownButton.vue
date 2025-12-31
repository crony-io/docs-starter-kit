<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

interface SelectScrollDownButtonProps {
  class?: HTMLAttributes['class'];
}

const props = defineProps<SelectScrollDownButtonProps>();
</script>

<template>
  <div
    aria-hidden="true"
    :class="cn('flex cursor-default items-center justify-center py-1', props.class)"
  >
    <slot>
      <ChevronDown class="h-4 w-4" />
    </slot>
  </div>
</template>
