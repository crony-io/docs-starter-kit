<script setup lang="ts">
import { useSelectRoot } from '@/composables/useSelect';
import { toRef } from 'vue';

interface SelectProps {
  modelValue?: string;
  defaultValue?: string;
  defaultOpen?: boolean;
  open?: boolean;
  disabled?: boolean;
  name?: string;
  autocomplete?: string;
  required?: boolean;
}

const props = withDefaults(defineProps<SelectProps>(), {
  disabled: false,
  defaultOpen: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
  'update:open': [open: boolean];
}>();

useSelectRoot({
  modelValue: toRef(() => props.modelValue),
  defaultValue: props.defaultValue,
  disabled: toRef(() => props.disabled),
  open: toRef(() => props.open),
  defaultOpen: props.defaultOpen,
  onUpdateModelValue: (value) => emit('update:modelValue', value),
  onUpdateOpen: (open) => emit('update:open', open),
});
</script>

<template>
  <div class="relative">
    <slot />
  </div>
</template>
