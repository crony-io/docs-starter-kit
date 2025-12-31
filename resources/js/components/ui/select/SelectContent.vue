<script setup lang="ts">
import { applyDynamicStyles } from '@/composables/useCspNonce';
import { useSelectContext } from '@/composables/useSelect';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { nextTick, onMounted, onUnmounted, Teleport, watch } from 'vue';

defineOptions({
  inheritAttrs: false,
});

interface SelectContentProps {
  class?: HTMLAttributes['class'];
  position?: 'item-aligned' | 'popper';
  side?: 'top' | 'right' | 'bottom' | 'left';
  sideOffset?: number;
  align?: 'start' | 'center' | 'end';
}

const props = withDefaults(defineProps<SelectContentProps>(), {
  position: 'popper',
  side: 'bottom',
  sideOffset: 4,
  align: 'start',
});

const context = useSelectContext();

const updatePosition = () => {
  if (!context.triggerRef.value || !context.open.value || !context.contentRef.value) return;

  const trigger = context.triggerRef.value;
  const rect = trigger.getBoundingClientRect();

  applyDynamicStyles(context.contentRef.value, {
    position: 'fixed',
    left: `${rect.left}px`,
    top: `${rect.bottom + props.sideOffset}px`,
    'min-width': `${rect.width}px`,
    'z-index': '50',
  });
};

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    event.preventDefault();
    context.close();
  }
};

watch(
  () => context.open.value,
  (open) => {
    if (open) {
      nextTick(() => {
        updatePosition();
        context.contentRef.value?.focus();
      });
    }
  },
);

onMounted(() => {
  window.addEventListener('resize', updatePosition);
  window.addEventListener('scroll', updatePosition, true);
});

onUnmounted(() => {
  window.removeEventListener('resize', updatePosition);
  window.removeEventListener('scroll', updatePosition, true);
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="context.open.value"
      :ref="(el) => (context.contentRef.value = el as HTMLElement)"
      role="listbox"
      :id="context.contentId"
      :aria-labelledby="context.triggerId"
      tabindex="-1"
      :data-state="context.open.value ? 'open' : 'closed'"
      :data-side="props.side"
      :class="
        cn(
          'relative z-50 max-h-96 min-w-32 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md',
          'animate-in fade-in-0 zoom-in-95',
          'data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
          position === 'popper' && 'data-[side=bottom]:translate-y-1',
          props.class,
        )
      "
      @keydown="handleKeydown"
    >
      <div class="p-1">
        <slot />
      </div>
    </div>
  </Teleport>
</template>
