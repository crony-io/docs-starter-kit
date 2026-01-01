<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { SwitchRootEmits, SwitchRootProps } from 'reka-ui';
import { SwitchRoot, SwitchThumb } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

interface Props extends /* @vue-ignore */ SwitchRootProps {
  class?: HTMLAttributes['class'];
  checked?: boolean;
}

const props = defineProps<Props>();

const emits = defineEmits<SwitchRootEmits & { 'update:checked': [value: boolean] }>();

const modelValue = computed({
  get: () => props.checked ?? props.modelValue ?? false,
  set: (value: boolean) => {
    emits('update:modelValue', value);
    emits('update:checked', value);
  },
});
</script>

<template>
  <SwitchRoot
    v-model="modelValue"
    :disabled="props.disabled"
    :required="props.required"
    :name="props.name"
    :value="props.value"
    :class="
      cn(
        'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input',
        props.class,
      )
    "
  >
    <SwitchThumb
      :class="
        cn(
          'pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-5',
        )
      "
    >
      <slot name="thumb" />
    </SwitchThumb>
  </SwitchRoot>
</template>
