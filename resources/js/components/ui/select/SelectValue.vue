<script setup lang="ts">
import { useSelectContext } from '@/composables/useSelect';
import { watch } from 'vue';

interface SelectValueProps {
  placeholder?: string;
}

const props = defineProps<SelectValueProps>();

const context = useSelectContext();

watch(
  () => props.placeholder,
  (val) => {
    if (val) context.placeholder.value = val;
  },
  { immediate: true },
);
</script>

<template>
  <span class="select-value-display">
    <slot v-if="context.selectedLabel.value || context.modelValue.value">
      {{ context.selectedLabel.value || context.modelValue.value }}
    </slot>
    <span v-else class="text-muted-foreground">{{ props.placeholder }}</span>
  </span>
</template>

<style scoped>
.select-value-display {
  pointer-events: none;
}
</style>
