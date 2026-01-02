<script setup lang="ts">
import PageTreeItem from '@/components/pages/PageTreeItem.vue';
import { dragOverParentId, isDragging, usePageTreeDrag } from '@/composables/usePageTreeDrag';
import type { PageTreeItem as PageTreeItemType } from '@/types/pages';
import { computed, ref } from 'vue';

const typeStyles = {
  navigation: 'border-l-4 border-l-primary bg-primary/5',
  group: 'border-l-4 border-l-amber-500 bg-amber-500/5',
  document: 'border-l-2 border-l-muted-foreground/30',
};

interface Props {
  items: PageTreeItemType[];
  parentId?: number | null;
  depth?: number;
  processingId?: number | null;
  selectedIds?: Set<number>;
  selectionMode?: boolean;
  allItems?: PageTreeItemType[];
}

const props = withDefaults(defineProps<Props>(), {
  parentId: null,
  depth: 0,
  processingId: null,
  selectedIds: () => new Set(),
  selectionMode: false,
  allItems: () => [],
});

const emit = defineEmits<{
  (e: 'reorder', data: { pages: { id: number; order: number; parent_id: number | null }[] }): void;
  (e: 'delete', item: PageTreeItemType): void;
  (e: 'duplicate', item: PageTreeItemType): void;
  (e: 'publish', item: PageTreeItemType): void;
  (e: 'unpublish', item: PageTreeItemType): void;
  (e: 'select', id: number, selected: boolean): void;
  (e: 'quickCreate', parentId: number): void;
  (e: 'move', pageId: number, newParentId: number | null, position: number): void;
}>();

const containerRef = ref<HTMLElement | null>(null);

const rootItems = computed(() => (props.allItems.length > 0 ? props.allItems : props.items));
const parentIdRef = computed(() => props.parentId);

usePageTreeDrag({
  containerRef,
  items: rootItems,
  parentId: parentIdRef,
  onMove: (pageId, newParentId, position) => emit('move', pageId, newParentId, position),
});

const isSelected = (id: number) => props.selectedIds.has(id);
</script>

<template>
  <div
    ref="containerRef"
    :data-parent-id="parentId ?? ''"
    :class="[
      depth === 0 ? 'space-y-2' : 'space-y-1',
      items.length === 0 &&
        isDragging &&
        dragOverParentId === parentId &&
        'flex min-h-[60px] items-center justify-center rounded-lg border-2 border-dashed border-primary/40 bg-primary/5',
      items.length === 0 && !(isDragging && dragOverParentId === parentId) && 'min-h-[8px]',
    ]"
  >
    <div v-for="item in items" :key="item.id" :data-id="item.id" :data-type="item.type">
      <PageTreeItem
        :item="item"
        :depth="depth"
        :processing-id="processingId"
        :is-selected="isSelected(item.id)"
        :selection-mode="selectionMode"
        :show-drag-handle="true"
        :type-styles="typeStyles"
        @delete="emit('delete', $event)"
        @duplicate="emit('duplicate', $event)"
        @publish="emit('publish', $event)"
        @unpublish="emit('unpublish', $event)"
        @select="(id: number, selected: boolean) => emit('select', id, selected)"
        @quick-create="emit('quickCreate', $event)"
      >
        <template #children="{ item: parentItem }">
          <PageTreeDraggable
            v-if="parentItem.type !== 'document'"
            :items="parentItem.children || []"
            :parent-id="parentItem.id"
            :depth="depth + 1"
            :processing-id="processingId"
            :selected-ids="selectedIds"
            :selection-mode="selectionMode"
            :all-items="rootItems"
            @reorder="emit('reorder', $event)"
            @delete="emit('delete', $event)"
            @duplicate="emit('duplicate', $event)"
            @publish="emit('publish', $event)"
            @unpublish="emit('unpublish', $event)"
            @select="(id: number, selected: boolean) => emit('select', id, selected)"
            @quick-create="emit('quickCreate', $event)"
            @move="
              (pageId: number, newParentId: number | null, pos: number) =>
                emit('move', pageId, newParentId, pos)
            "
          />
        </template>
      </PageTreeItem>
    </div>
  </div>
</template>
