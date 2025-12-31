<script setup lang="ts">
import { useSelectContext } from '@/composables/useSelect';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { computed, ref } from 'vue';

interface SelectItemProps {
  class?: HTMLAttributes['class'];
  value: string;
  disabled?: boolean;
  textValue?: string;
}

const props = defineProps<SelectItemProps>();

const context = useSelectContext();
const itemRef = ref<HTMLElement | null>(null);

const isSelected = computed(() => context.modelValue.value === props.value);

const getTextContent = (): string => {
  if (props.textValue) return props.textValue;
  if (itemRef.value) return itemRef.value.textContent?.trim() || props.value;
  return props.value;
};

const handleSelect = () => {
  if (props.disabled) return;
  const label = getTextContent();
  context.updateValue(props.value, label);
};

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    handleSelect();
  }
};
</script>

<template>
  <div
    ref="itemRef"
    role="option"
    :aria-selected="isSelected"
    :aria-disabled="props.disabled"
    :data-state="isSelected ? 'checked' : 'unchecked'"
    :data-disabled="props.disabled ? '' : undefined"
    :data-highlighted="undefined"
    tabindex="0"
    :class="
      cn(
        'relative flex w-full cursor-default items-center rounded-sm py-1.5 pr-2 pl-8 text-sm outline-none select-none focus:bg-accent focus:text-accent-foreground data-disabled:pointer-events-none data-disabled:opacity-50',
        props.class,
      )
    "
    @click="handleSelect"
    @keydown="handleKeydown"
  >
    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <Check v-if="isSelected" class="h-4 w-4" />
    </span>

    <span class="select-item-text">
      <slot />
    </span>
  </div>
</template>

<style scoped>
.select-item-text {
  pointer-events: none;
}
</style>
