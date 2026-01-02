import { reorder } from '@/routes/admin/pages';
import type { PageTreeItem } from '@/types/pages';
import { router } from '@inertiajs/vue3';
import Sortable, { type SortableEvent } from 'sortablejs';
import { onMounted, onUnmounted, ref, watch, type Ref } from 'vue';

export const isDragging = ref(false);
export const dragOverParentId = ref<number | null | undefined>(undefined);
export const expandedByDrag = ref<Set<number>>(new Set());

let hoverTimeout: ReturnType<typeof setTimeout> | null = null;
const HOVER_DELAY = 500;

export interface UsePageTreeDragOptions {
  containerRef: Ref<HTMLElement | null>;
  items: Ref<PageTreeItem[]>;
  parentId: Ref<number | null>;
  onMove: (pageId: number, newParentId: number | null, position: number) => void;
}

export function findItemById(items: PageTreeItem[], id: number): PageTreeItem | null {
  for (const item of items) {
    if (item.id === id) {
      return item;
    }
    if (item.children?.length) {
      const found = findItemById(item.children, id);
      if (found) {
        return found;
      }
    }
  }
  return null;
}

export function getAllDescendantIds(item: PageTreeItem): number[] {
  const ids: number[] = [];
  if (item.children) {
    for (const child of item.children) {
      ids.push(child.id);
      ids.push(...getAllDescendantIds(child));
    }
  }
  return ids;
}

export function canMoveToParent(
  draggedItem: PageTreeItem,
  targetParentId: number | null,
  rootItems: PageTreeItem[],
): boolean {
  // Navigation must stay at root level
  if (draggedItem.type === 'navigation' && targetParentId !== null) {
    return false;
  }

  // Documents cannot be at root level
  if (draggedItem.type === 'document' && targetParentId === null) {
    return false;
  }

  // If moving to root, allow navigation and groups
  if (targetParentId === null) {
    return true;
  }

  // Cannot move into itself
  if (targetParentId === draggedItem.id) {
    return false;
  }

  // Cannot move into descendants
  const descendantIds = getAllDescendantIds(draggedItem);
  if (descendantIds.includes(targetParentId)) {
    return false;
  }

  // Check target parent exists and is not a document
  const targetParent = findItemById(rootItems, targetParentId);
  if (!targetParent || targetParent.type === 'document') {
    return false;
  }

  return true;
}

export function usePageTreeDrag(options: UsePageTreeDragOptions) {
  const { containerRef, items, parentId, onMove } = options;
  let sortableInstance: Sortable | null = null;

  const getRootItems = (): PageTreeItem[] => {
    return items.value;
  };

  const initSortable = () => {
    if (!containerRef.value) {
      return;
    }

    sortableInstance = Sortable.create(containerRef.value, {
      group: {
        name: 'pages',
        pull: true,
        put: (_to, _from, dragEl) => {
          const draggedId = Number(dragEl.dataset.id);
          const draggedType = dragEl.dataset.type as PageTreeItem['type'];

          // Navigation must stay at root
          if (draggedType === 'navigation' && parentId.value !== null) {
            return false;
          }

          // Documents cannot be at root
          if (draggedType === 'document' && parentId.value === null) {
            return false;
          }

          const draggedItem = findItemById(getRootItems(), draggedId);
          if (!draggedItem) {
            const simpleItem: PageTreeItem = {
              id: draggedId,
              title: '',
              slug: '',
              type: draggedType,
              status: 'draft',
              updated_at: '',
              children: [],
            };
            return canMoveToParent(simpleItem, parentId.value, getRootItems());
          }

          return canMoveToParent(draggedItem, parentId.value, getRootItems());
        },
      },
      animation: 150,
      handle: '.drag-handle',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag',
      fallbackOnBody: true,
      swapThreshold: 0.65,
      emptyInsertThreshold: 20,
      forceFallback: true,
      onStart: () => {
        isDragging.value = true;
        dragOverParentId.value = undefined;
        expandedByDrag.value = new Set();
        if (hoverTimeout) {
          clearTimeout(hoverTimeout);
          hoverTimeout = null;
        }
      },
      onMove: (evt) => {
        const draggedId = Number(evt.dragged.dataset.id);
        const draggedType = evt.dragged.dataset.type as PageTreeItem['type'];

        const targetContainer = evt.to;
        const parentIdAttr = targetContainer.dataset.parentId;
        const targetParentId = parentIdAttr && parentIdAttr !== '' ? Number(parentIdAttr) : null;

        if (dragOverParentId.value !== targetParentId) {
          if (hoverTimeout) {
            clearTimeout(hoverTimeout);
            hoverTimeout = null;
          }

          dragOverParentId.value = undefined;

          if (targetParentId !== null) {
            hoverTimeout = setTimeout(() => {
              dragOverParentId.value = targetParentId;
              expandedByDrag.value.add(targetParentId);
            }, HOVER_DELAY);
          } else {
            dragOverParentId.value = targetParentId;
          }
        }

        // Navigation must stay at root
        if (draggedType === 'navigation' && targetParentId !== null) {
          return false;
        }

        // Documents cannot be at root
        if (draggedType === 'document' && targetParentId === null) {
          return false;
        }

        const draggedItem = findItemById(getRootItems(), draggedId);
        if (!draggedItem) {
          return true;
        }

        return canMoveToParent(draggedItem, targetParentId, getRootItems());
      },
      onEnd: (evt: SortableEvent) => {
        isDragging.value = false;
        dragOverParentId.value = undefined;
        if (hoverTimeout) {
          clearTimeout(hoverTimeout);
          hoverTimeout = null;
        }

        if (evt.oldIndex === undefined || evt.newIndex === undefined) {
          return;
        }

        const draggedId = Number(evt.item.dataset.id);
        const fromContainer = evt.from;
        const toContainer = evt.to;

        const fromParentAttr = fromContainer.dataset.parentId;
        const toParentAttr = toContainer.dataset.parentId;
        const fromParentId =
          fromParentAttr && fromParentAttr !== '' ? Number(fromParentAttr) : null;
        const toParentId = toParentAttr && toParentAttr !== '' ? Number(toParentAttr) : null;

        if (fromParentId !== toParentId) {
          onMove(draggedId, toParentId, evt.newIndex);
        } else {
          const newOrder = Array.from(toContainer.children)
            .filter((el) => (el as HTMLElement).dataset.id)
            .map((el, index) => ({
              id: Number((el as HTMLElement).dataset.id),
              order: index,
              parent_id: toParentId,
            }));

          if (newOrder.length > 0) {
            router.post(
              reorder().url,
              { pages: newOrder },
              {
                preserveScroll: true,
                preserveState: true,
              },
            );
          }
        }
      },
    });
  };

  const destroySortable = () => {
    if (sortableInstance) {
      sortableInstance.destroy();
      sortableInstance = null;
    }
  };

  onMounted(() => {
    initSortable();
  });

  onUnmounted(() => {
    destroySortable();
  });

  watch(items, () => {
    destroySortable();
    initSortable();
  });

  return {
    initSortable,
    destroySortable,
  };
}
