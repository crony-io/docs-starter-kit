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
import { expandedByDrag, isDragging } from '@/composables/usePageTreeDrag';
import { edit } from '@/routes/admin/pages';
import type { PageTreeItem } from '@/types/pages';
import { Link } from '@inertiajs/vue3';
import {
  Book,
  Check,
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
import { computed, ref, watch } from 'vue';

interface Props {
  item: PageTreeItem;
  depth?: number;
  processingId?: number | null;
  isSelected?: boolean;
  selectionMode?: boolean;
  showDragHandle?: boolean;
  typeStyles?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
  processingId: null,
  isSelected: false,
  selectionMode: false,
  showDragHandle: false,
  typeStyles: () => ({
    navigation: 'border-l-4 border-l-primary bg-primary/5',
    group: 'border-l-4 border-l-amber-500 bg-amber-500/5',
    document: 'border-l-2 border-l-muted-foreground/30',
  }),
});

const emit = defineEmits<{
  (e: 'delete', item: PageTreeItem): void;
  (e: 'duplicate', item: PageTreeItem): void;
  (e: 'publish', item: PageTreeItem): void;
  (e: 'unpublish', item: PageTreeItem): void;
  (e: 'select', id: number, selected: boolean): void;
  (e: 'quickCreate', parentId: number): void;
}>();

const typeIcons = {
  navigation: Book,
  group: FolderTree,
  document: FileText,
};

const typeLabels = {
  navigation: 'Navigation',
  group: 'Group',
  document: 'Document',
};

const manualOpen = ref(props.item.type !== 'document');

const isOpen = computed(() => {
  if (isDragging.value && expandedByDrag.value.has(props.item.id)) {
    return true;
  }
  return manualOpen.value;
});

const setOpen = (value: boolean) => {
  manualOpen.value = value;
};

watch(
  () => props.item.children?.length,
  (len, oldLen) => {
    if (len && len > 0 && (!oldLen || oldLen === 0)) {
      manualOpen.value = true;
    }
  },
);
</script>

<template>
  <div
    class="rounded-lg border bg-card transition-all duration-200 hover:shadow-sm"
    :class="[
      typeStyles[item.type],
      {
        'opacity-50': processingId === item.id,
        'shadow-md ring-2 ring-primary/50': isSelected,
      },
    ]"
  >
    <Collapsible :open="isOpen" @update:open="setOpen">
      <div class="group flex items-center gap-2 px-3 py-2.5" :class="{ 'ml-4': depth > 0 }">
        <div
          v-if="selectionMode"
          class="mr-1 flex size-4 shrink-0 cursor-pointer items-center justify-center rounded-[4px] border shadow-xs transition-shadow"
          :class="
            isSelected
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-input bg-background'
          "
          @click.stop="emit('select', item.id, !isSelected)"
        >
          <Check v-if="isSelected" class="size-3.5" />
        </div>

        <div
          v-if="showDragHandle"
          class="drag-handle cursor-grab text-muted-foreground/50 transition-colors group-hover:text-muted-foreground hover:text-foreground active:cursor-grabbing"
        >
          <GripVertical class="h-4 w-4" />
        </div>

        <CollapsibleTrigger
          v-if="item.type !== 'document'"
          as="button"
          class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-accent"
        >
          <ChevronRight
            class="h-4 w-4 transition-transform duration-200"
            :class="{ 'rotate-90': isOpen }"
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
            <div class="flex items-center gap-2">
              <h3 class="truncate font-medium transition-colors hover:text-primary">
                {{ item.title }}
              </h3>
              <span
                class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium tracking-wide uppercase"
                :class="{
                  'bg-primary/10 text-primary': item.type === 'navigation',
                  'bg-amber-500/10 text-amber-600': item.type === 'group',
                  'bg-muted text-muted-foreground': item.type === 'document',
                }"
              >
                {{ typeLabels[item.type] }}
              </span>
            </div>
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
            v-if="item.status !== 'published'"
            variant="ghost"
            size="icon"
            class="h-8 w-8 text-green-600 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-950"
            :disabled="processingId === item.id"
            title="Publish"
            @click="emit('publish', item)"
          >
            <Eye class="h-4 w-4" />
          </Button>
          <Button
            v-else
            variant="ghost"
            size="icon"
            class="h-8 w-8 text-amber-600 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-amber-50 hover:text-amber-700 dark:hover:bg-amber-950"
            :disabled="processingId === item.id"
            title="Unpublish"
            @click="emit('unpublish', item)"
          >
            <EyeOff class="h-4 w-4" />
          </Button>

          <Button
            v-if="item.type !== 'document'"
            variant="ghost"
            size="icon"
            class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100"
            title="Add child"
            @click="emit('quickCreate', item.id)"
          >
            <Plus class="h-4 w-4" />
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
              <DropdownMenuItem v-if="item.status === 'published'" @click="emit('unpublish', item)">
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

      <CollapsibleContent v-if="item.type !== 'document'">
        <div class="">
          <slot name="children" :item="item" :is-open="isOpen" />
        </div>
      </CollapsibleContent>
    </Collapsible>
  </div>
</template>
