<script setup lang="ts">
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import BulkActionsToolbar from '@/components/pages/BulkActionsToolbar.vue';
import PageFilters from '@/components/pages/PageFilters.vue';
import PagesEmptyState from '@/components/pages/PagesEmptyState.vue';
import PageTreeDraggable from '@/components/pages/PageTreeDraggable.vue';
import QuickCreateDialog from '@/components/pages/QuickCreateDialog.vue';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useBulkSelection } from '@/composables/useBulkSelection';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
  create,
  destroy,
  duplicate,
  index as pagesIndex,
  publish,
  unpublish,
} from '@/routes/admin/pages';
import type { BreadcrumbItem, StatusOption } from '@/types';
import type { Page, PageTreeItem } from '@/types/pages';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { CheckSquare, ChevronDown, FolderTree, Plus } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
  treeData: PageTreeItem[];
  navigationTabs: Pick<Page, 'id' | 'title' | 'slug'>[];
  potentialParents: Pick<Page, 'id' | 'title' | 'slug' | 'type' | 'parent_id'>[];
  filters: { status?: string; search?: string; type?: string };
  statuses: StatusOption[];
  types: StatusOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Pages', href: pagesIndex().url },
];

const {
  selectedIds,
  selectionMode,
  isBulkProcessing,
  selectedCount,
  toggleSelectionMode,
  handleSelect,
  clearSelection,
  bulkPublish,
  bulkUnpublish,
  bulkDelete: doBulkDelete,
} = useBulkSelection();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status || 'all');
const typeFilter = ref(props.filters.type || 'all');
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<PageTreeItem | null>(null);
const processingId = ref<number | null>(null);
const isQuickCreateOpen = ref(false);
const quickCreateParentId = ref<number | null>(null);

const openQuickCreate = (parentId?: number) => {
  quickCreateParentId.value = parentId ?? null;
  isQuickCreateOpen.value = true;
};

const applyFilters = useDebounceFn(() => {
  router.get(
    pagesIndex().url,
    {
      search: search.value || undefined,
      status: statusFilter.value === 'all' ? undefined : statusFilter.value,
      type: typeFilter.value === 'all' ? undefined : typeFilter.value,
    },
    { preserveState: true, replace: true },
  );
}, 300);

watch([search], () => applyFilters());

const onStatusChange = (value: unknown) => {
  statusFilter.value = String(value ?? '');
  applyFilters();
};

const onTypeChange = (value: unknown) => {
  typeFilter.value = String(value ?? '');
  applyFilters();
};

const openDeleteDialog = (item: PageTreeItem) => {
  itemToDelete.value = item;
  isDeleteDialogOpen.value = true;
};

const bulkDelete = () => {
  itemToDelete.value = {
    id: -1,
    title: `${selectedIds.value.size} items`,
    slug: '',
    type: 'document',
    status: 'draft',
    updated_at: '',
    children: [],
  };
  isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
  if (!itemToDelete.value) {
    return;
  }

  if (itemToDelete.value.id === -1) {
    doBulkDelete(() => {
      isDeleteDialogOpen.value = false;
      itemToDelete.value = null;
    });
    return;
  }

  processingId.value = itemToDelete.value.id;
  router.delete(destroy(itemToDelete.value.id).url, {
    onFinish: () => {
      processingId.value = null;
      isDeleteDialogOpen.value = false;
      itemToDelete.value = null;
    },
  });
};

const handleDuplicate = (item: PageTreeItem) => {
  processingId.value = item.id;
  router.post(duplicate(item.id).url, {}, { onFinish: () => (processingId.value = null) });
};

const handlePublish = (item: PageTreeItem) => {
  processingId.value = item.id;
  router.post(publish(item.id).url, {}, { onFinish: () => (processingId.value = null) });
};

const handleUnpublish = (item: PageTreeItem) => {
  processingId.value = item.id;
  router.post(unpublish(item.id).url, {}, { onFinish: () => (processingId.value = null) });
};

const handleMove = (pageId: number, newParentId: number | null, position: number) => {
  processingId.value = pageId;
  router.post(
    `/admin/pages/${pageId}/move`,
    { parent_id: newParentId, position },
    {
      preserveScroll: true,
      onFinish: () => {
        processingId.value = null;
      },
    },
  );
};

const handleKeyDown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'n' && !e.shiftKey) {
    e.preventDefault();
    router.visit(create().url);
  }
};

onMounted(() => window.addEventListener('keydown', handleKeyDown));
onUnmounted(() => window.removeEventListener('keydown', handleKeyDown));
</script>

<template>
  <Head title="Pages Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <Heading title="Pages" description="Manage your documentation pages" />
        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            :class="{ 'bg-accent': selectionMode }"
            @click="toggleSelectionMode"
          >
            <CheckSquare class="mr-2 h-4 w-4" />
            {{ selectionMode ? 'Cancel' : 'Select' }}
          </Button>

          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="outline">
                <FolderTree class="mr-2 h-4 w-4" />
                Quick Add
                <ChevronDown class="ml-2 h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem @click="openQuickCreate()">
                <Plus class="mr-2 h-4 w-4" />
                New Navigation / Group
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem as-child>
                <Link :href="create()" class="flex items-center">
                  <Plus class="mr-2 h-4 w-4" />
                  New Page (Full Editor)
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <Button as-child>
            <Link :href="create()">
              <Plus class="mr-2 h-4 w-4" />
              Create Page
              <kbd
                class="ml-2 hidden rounded bg-muted px-1.5 py-0.5 text-xs font-medium text-muted-foreground sm:inline-block"
              >
                Ctrl+N
              </kbd>
            </Link>
          </Button>
        </div>
      </div>

      <div class="mt-6">
        <PageFilters
          :search="search"
          :status-filter="statusFilter"
          :type-filter="typeFilter"
          :statuses="statuses"
          :types="types"
          @update:search="search = $event"
          @update:status-filter="onStatusChange"
          @update:type-filter="onTypeChange"
        />
      </div>

      <BulkActionsToolbar
        v-if="selectionMode"
        class="mt-4"
        :selected-count="selectedCount"
        :processing="isBulkProcessing"
        @clear="clearSelection"
        @publish="bulkPublish"
        @unpublish="bulkUnpublish"
        @delete="bulkDelete"
      />

      <div class="mt-6">
        <PagesEmptyState v-if="treeData.length === 0" />
        <PageTreeDraggable
          v-else
          :items="treeData"
          :processing-id="processingId"
          :selected-ids="selectedIds"
          :selection-mode="selectionMode"
          @delete="openDeleteDialog"
          @duplicate="handleDuplicate"
          @publish="handlePublish"
          @unpublish="handleUnpublish"
          @select="handleSelect"
          @quick-create="openQuickCreate"
          @move="handleMove"
        />
      </div>
    </div>

    <DeleteConfirmDialog
      v-model:open="isDeleteDialogOpen"
      title="Delete this page?"
      description="This action cannot be undone. This will permanently delete the page"
      :item-name="itemToDelete?.title"
      :processing="processingId !== null || isBulkProcessing"
      @confirm="confirmDelete"
    />

    <QuickCreateDialog
      v-model:open="isQuickCreateOpen"
      :potential-parents="potentialParents"
      :default-parent-id="quickCreateParentId"
    />
  </AppLayout>
</template>
