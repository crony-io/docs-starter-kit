<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, shallowRef, type Component } from 'vue';

interface Props {
  name: string;
  class?: string;
  size?: number | string;
  color?: string;
  strokeWidth?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
  class: '',
  size: 16,
  strokeWidth: 2,
});

const className = computed(() => cn('h-4 w-4', props.class));

const iconsModule = shallowRef<Record<string, Component> | null>(null);

const loadIcons = async () => {
  if (!iconsModule.value) {
    const module = await import('lucide-vue-next');
    iconsModule.value = module as unknown as Record<string, Component>;
  }
};

loadIcons();

const icon = computed(() => {
  if (!iconsModule.value || !props.name) {
    return null;
  }
  const pascalName = props.name
    .split('-')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');
  return iconsModule.value[pascalName] || null;
});
</script>

<template>
  <component
    v-if="icon"
    :is="icon"
    :class="className"
    :size="size"
    :stroke-width="strokeWidth"
    :color="color"
  />
</template>
