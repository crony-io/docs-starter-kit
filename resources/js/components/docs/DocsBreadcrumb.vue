<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import type { BreadcrumbItem } from '@/types/docs';
import { computed } from 'vue';

interface Props {
  items: BreadcrumbItem[];
}

const props = defineProps<Props>();

const breadcrumbItems = computed(() => {
  const items: Array<{ title: string; href?: string }> = [{ title: 'Docs', href: '/docs' }];

  props.items.forEach((item, index) => {
    const isLast = index === props.items.length - 1;
    items.push({
      title: item.title,
      href: isLast ? undefined : `/docs/${item.path}`,
    });
  });

  return items;
});
</script>

<template>
  <div v-if="items.length > 0" class="mb-4">
    <Breadcrumbs :breadcrumbs="breadcrumbItems" />
  </div>
</template>
