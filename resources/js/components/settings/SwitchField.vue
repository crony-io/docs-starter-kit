<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { computed } from 'vue';

interface Props {
  label: string;
  description?: string;
  modelValue: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();

const checked = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
});
</script>

<template>
  <div class="flex items-center justify-between rounded-lg border p-3">
    <div>
      <Label class="text-base">{{ label }}</Label>
      <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
    </div>
    <Switch v-model:checked="checked" />
  </div>
</template>
