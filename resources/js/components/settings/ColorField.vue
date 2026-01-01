<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';

interface Props {
  label: string;
  modelValue: string;
  error?: string;
  placeholder?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

const colorValue = computed({
  get: () => props.modelValue || '#000000',
  set: (value: string) => emit('update:modelValue', value),
});

const textValue = computed({
  get: () => props.modelValue || '',
  set: (value: string) => emit('update:modelValue', value),
});
</script>

<template>
  <div class="space-y-2">
    <Label>{{ label }}</Label>
    <div class="flex gap-2">
      <input
        type="color"
        v-model="colorValue"
        class="h-10 w-14 cursor-pointer rounded border bg-background p-1"
      />
      <Input v-model="textValue" :placeholder="placeholder" class="flex-1 font-mono" />
    </div>
    <InputError :message="error" />
  </div>
</template>
