<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { create, edit, reorder } from '@/routes/admin/pages';
import { Link, router } from '@inertiajs/vue3';
import {
  Book,
  ChevronRight,
  Copy,
  Eye,
  EyeOff,
  FileText,
  FolderTree,
  GripVertical,
  MoreHorizontal,
  Pencil,
  Plus,
  Trash2,
} from 'lucide-vue-next';
import Sortable, { type SortableEvent } from 'sortablejs';
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface TreeItem {
  id: number;
  title: string;
  slug: string;
  type: 'navigation' | 'group' | 'document';
  status: 'draft' | 'published' | 'archived';
  updated_at: string;
  children: TreeItem[];
}

interface Props {
  items: TreeItem[];
  parentId?: number | null;
  depth?: number;
  processingId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
  parentId: null,
  depth: 0,
  processingId: null,
});

const emit = defineEmits<{
  (e: 'reorder', data: { pages: { id: number; order: number; parent_id: number | null }[] }): void;
  (e: 'delete', item: TreeItem): void;
  (e: 'duplicate', item: TreeItem): void;
  (e: 'publish', item: TreeItem): void;
  (e: 'unpublish', item: TreeItem): void;
}>();

const typeIcons = {
  navigation: Book,
  group: FolderTree,
  document: FileText,
};

const typeStyles = {
  navigation: 'border-l-4 border-l-primary bg-primary/5',
  group: 'border-l-4 border-l-amber-500 bg-amber-500/5',
  document: 'border-l-2 border-l-muted-foreground/30',
};

const containerRef = ref<HTMLElement | null>(null);
const expandedItems = ref<Set<number>>(new Set());
let sortableInstance: Sortable | null = null;

const toggleExpanded = (id: number) => {
  if (expandedItems.value.has(id)) {
    expandedItems.value.delete(id);
  } else {
    expandedItems.value.add(id);
  }
  expandedItems.value = new Set(expandedItems.value);
};

const isExpanded = (id: number) => expandedItems.value.has(id);

const initSortable = () => {
  if (!containerRef.value) {
    return;
  }

  sortableInstance = Sortable.create(containerRef.value, {
    group: 'pages',
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'opacity-50',
    onEnd: (evt: SortableEvent) => {
      if (evt.oldIndex === undefined || evt.newIndex === undefined) {
        return;
      }

      const newOrder = Array.from(containerRef.value!.children).map((el, index) => ({
        id: Number((el as HTMLElement).dataset.id),
        order: index,
        parent_id: props.parentId,
      }));

      router.post(
        reorder().url,
        { pages: newOrder },
        {
          preserveScroll: true,
          preserveState: true,
        },
      );
    },
  });
};

onMounted(() => {
  initSortable();
  props.items.forEach((item) => {
    if (item.children?.length > 0) {
      expandedItems.value.add(item.id);
    }
  });
});

onUnmounted(() => {
  if (sortableInstance) {
    sortableInstance.destroy();
  }
});

watch(
  () => props.items,
  () => {
    if (sortableInstance) {
      sortableInstance.destroy();
    }
    initSortable();
  },
);
</script>

<template>
  <div ref="containerRef" :class="depth === 0 ? 'space-y-2' : 'space-y-1 py-1'">
    <div
      v-for="item in items"
      :key="item.id"
      :data-id="item.id"
      class="rounded-lg border bg-card transition-shadow hover:shadow-sm"
      :class="[typeStyles[item.type], { 'opacity-50': processingId === item.id }]"
    >
      <Collapsible :open="isExpanded(item.id)">
        <div class="group flex items-center gap-3 px-3 py-2.5" :class="{ 'ml-4': depth > 0 }">
          <div
            class="drag-handle cursor-grab text-muted-foreground/50 transition-colors group-hover:text-muted-foreground hover:text-foreground"
          >
            <GripVertical class="h-4 w-4" />
          </div>

          <CollapsibleTrigger
            v-if="item.children?.length > 0"
            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-accent"
            @click="toggleExpanded(item.id)"
          >
            <ChevronRight
              class="h-4 w-4 transition-transform duration-200"
              :class="{ 'rotate-90': isExpanded(item.id) }"
            />
          </CollapsibleTrigger>
          <div v-else class="h-6 w-6 shrink-0" />

          <div class="flex min-w-0 flex-1 items-center gap-3">
            <div
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
              :class="{
                'bg-primary/10 text-primary': item.type === 'navigation',
                'bg-amber-500/10 text-amber-600': item.type === 'group',
                'bg-muted text-muted-foreground': item.type === 'document',
              }"
            >
              <component :is="typeIcons[item.type]" class="h-4 w-4" />
            </div>

            <div class="min-w-0 flex-1">
              <h3 class="block truncate font-medium transition-colors hover:text-primary">
                {{ item.title }}
              </h3>
              <p class="truncate text-xs text-muted-foreground">
                /{{ item.slug }}
                <span v-if="item.children?.length > 0" class="ml-2">
                  · {{ item.children.length }}
                  {{ item.children.length === 1 ? 'child' : 'children' }}
                </span>
              </p>
            </div>
          </div>

          <StatusBadge :status="item.status" />

          <div class="flex shrink-0 items-center gap-1">
            <Button
              v-if="item.type !== 'document'"
              variant="ghost"
              size="icon"
              class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100"
              as-child
              title="Add child page"
            >
              <Link :href="create({ query: { parent_id: item.id } })">
                <Plus class="h-4 w-4" />
              </Link>
            </Button>

            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100"
              as-child
              title="Edit page"
            >
              <Link :href="edit(item.id)">
                <Pencil class="h-4 w-4" />
              </Link>
            </Button>

            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-8 w-8"
                  :disabled="processingId === item.id"
                >
                  <MoreHorizontal class="h-4 w-4" />
                  <span class="sr-only">More actions</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuItem as-child>
                  <Link :href="edit(item.id)" class="flex items-center">
                    <Pencil class="mr-2 h-4 w-4" />
                    Edit
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem @click="emit('duplicate', item)">
                  <Copy class="mr-2 h-4 w-4" />
                  Duplicate
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem v-if="item.status !== 'published'" @click="emit('publish', item)">
                  <Eye class="mr-2 h-4 w-4" />
                  Publish
                </DropdownMenuItem>
                <DropdownMenuItem
                  v-if="item.status === 'published'"
                  @click="emit('unpublish', item)"
                >
                  <EyeOff class="mr-2 h-4 w-4" />
                  Unpublish
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  class="text-destructive focus:text-destructive"
                  :disabled="(item.children?.length ?? 0) > 0"
                  @click="emit('delete', item)"
                >
                  <Trash2 class="mr-2 h-4 w-4" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>

        <CollapsibleContent v-if="item.children?.length > 0">
          <div class="border-t bg-muted/30 px-2 pb-2">
            <PageTreeDraggable
              :items="item.children"
              :parent-id="item.id"
              :depth="depth + 1"
              :processing-id="processingId"
              @reorder="emit('reorder', $event)"
              @delete="emit('delete', $event)"
              @duplicate="emit('duplicate', $event)"
              @publish="emit('publish', $event)"
              @unpublish="emit('unpublish', $event)"
            />
          </div>
        </CollapsibleContent>
      </Collapsible>
    </div>
  </div>
</template>
