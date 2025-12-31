<script setup lang="ts">
import { useSelectContext } from '@/composables/useSelect';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { onMounted, onUnmounted } from 'vue';

interface SelectTriggerProps {
  class?: HTMLAttributes['class'];
  disabled?: boolean;
}

const props = defineProps<SelectTriggerProps>();

const context = useSelectContext();

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
    event.preventDefault();
    if (!context.open.value) {
      context.toggle();
    }
  } else if (event.key === 'Escape') {
    event.preventDefault();
    context.close();
  }
};

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (
    context.open.value &&
    !context.triggerRef.value?.contains(target) &&
    !context.contentRef.value?.contains(target)
  ) {
    context.close();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <button
    :ref="(el) => (context.triggerRef.value = el as HTMLElement)"
    type="button"
    role="combobox"
    :aria-expanded="context.open.value"
    :aria-controls="context.contentId"
    :aria-disabled="context.disabled.value || props.disabled"
    :disabled="context.disabled.value || props.disabled"
    :data-state="context.open.value ? 'open' : 'closed'"
    :data-disabled="context.disabled.value || props.disabled ? '' : undefined"
    :data-placeholder="!context.modelValue.value ? '' : undefined"
    :class="
      cn(
        'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-start text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-placeholder:text-muted-foreground [&>span]:truncate',
        props.class,
      )
    "
    @click.stop="context.toggle()"
    @keydown="handleKeydown"
  >
    <slot />
    <ChevronDown class="h-4 w-4 shrink-0 opacity-50" aria-hidden="true" />
  </button>
</template>
