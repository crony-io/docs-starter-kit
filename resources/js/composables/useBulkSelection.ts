import { destroy, publish, unpublish } from '@/routes/admin/pages';
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

export function useBulkSelection() {
  const selectedIdsMap = reactive<Record<number, boolean>>({});
  const selectionMode = ref(false);
  const isBulkProcessing = ref(false);

  const selectedIds = computed(() => {
    const ids = Object.keys(selectedIdsMap)
      .filter((key) => selectedIdsMap[Number(key)])
      .map(Number);
    return new Set(ids);
  });

  const selectedCount = computed(() => selectedIds.value.size);

  const toggleSelectionMode = () => {
    selectionMode.value = !selectionMode.value;
    if (!selectionMode.value) {
      Object.keys(selectedIdsMap).forEach((key) => delete selectedIdsMap[Number(key)]);
    }
  };

  const handleSelect = (id: number, selected: boolean) => {
    if (selected) {
      selectedIdsMap[id] = true;
    } else {
      delete selectedIdsMap[id];
    }
  };

  const clearSelection = () => {
    Object.keys(selectedIdsMap).forEach((key) => delete selectedIdsMap[Number(key)]);
  };

  const bulkPublish = () => {
    if (selectedIds.value.size === 0) {
      return;
    }
    isBulkProcessing.value = true;
    const ids = Array.from(selectedIds.value);
    let completed = 0;

    ids.forEach((id) => {
      router.post(
        publish(id).url,
        {},
        {
          preserveScroll: true,
          onFinish: () => {
            completed++;
            if (completed === ids.length) {
              isBulkProcessing.value = false;
              clearSelection();
            }
          },
        },
      );
    });
  };

  const bulkUnpublish = () => {
    if (selectedIds.value.size === 0) {
      return;
    }
    isBulkProcessing.value = true;
    const ids = Array.from(selectedIds.value);
    let completed = 0;

    ids.forEach((id) => {
      router.post(
        unpublish(id).url,
        {},
        {
          preserveScroll: true,
          onFinish: () => {
            completed++;
            if (completed === ids.length) {
              isBulkProcessing.value = false;
              clearSelection();
            }
          },
        },
      );
    });
  };

  const bulkDelete = (onComplete: () => void) => {
    if (selectedIds.value.size === 0) {
      return;
    }
    isBulkProcessing.value = true;
    const ids = Array.from(selectedIds.value);
    let completed = 0;

    ids.forEach((id) => {
      router.delete(destroy(id).url, {
        preserveScroll: true,
        onFinish: () => {
          completed++;
          if (completed === ids.length) {
            isBulkProcessing.value = false;
            clearSelection();
            onComplete();
          }
        },
      });
    });
  };

  return {
    selectedIds,
    selectionMode,
    isBulkProcessing,
    selectedCount,
    toggleSelectionMode,
    handleSelect,
    clearSelection,
    bulkPublish,
    bulkUnpublish,
    bulkDelete,
  };
}
