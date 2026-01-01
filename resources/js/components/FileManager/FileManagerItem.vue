<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuSeparator,
  ContextMenuTrigger,
} from '@/components/ui/context-menu';
import type { MediaFile } from '@/types/media';
import {
  ExternalLinkIcon,
  FileIcon,
  FileTextIcon,
  FolderInputIcon,
  GripVerticalIcon,
  ImageIcon,
  InfoIcon,
  MusicIcon,
  PencilIcon,
  Trash2Icon,
  VideoIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
  file: MediaFile;
  selected?: boolean;
  selectable?: boolean;
  viewMode?: 'grid' | 'list';
}

const props = withDefaults(defineProps<Props>(), {
  selected: false,
  selectable: true,
  viewMode: 'grid',
});

const emit = defineEmits<{
  select: [file: MediaFile];
  toggle: [file: MediaFile];
  viewDetails: [file: MediaFile];
  rename: [file: MediaFile];
  move: [file: MediaFile];
  delete: [file: MediaFile];
  dragStart: [file: MediaFile];
  dragEnd: [];
}>();

const isDragging = ref(false);

const handleDragStart = (e: DragEvent) => {
  isDragging.value = true;
  e.dataTransfer?.setData('application/json', JSON.stringify({ type: 'file', id: props.file.id }));
  e.dataTransfer!.effectAllowed = 'move';
  emit('dragStart', props.file);
};

const handleDragEnd = () => {
  isDragging.value = false;
  emit('dragEnd');
};

const fileIcon = computed(() => {
  switch (props.file.file_type) {
    case 'image':
      return ImageIcon;
    case 'video':
      return VideoIcon;
    case 'audio':
      return MusicIcon;
    case 'document':
      return FileTextIcon;
    default:
      return FileIcon;
  }
});

const formattedSize = computed(() => {
  return props.file.human_size ?? '';
});

const handleClick = () => {
  emit('select', props.file);
};

const handleCheckboxChange = (_checked: boolean) => {
  emit('toggle', props.file);
};
</script>

<template>
  <ContextMenu>
    <ContextMenuTrigger as-child>
      <!-- Grid View -->
      <div
        v-if="viewMode === 'grid'"
        draggable="true"
        class="group relative cursor-pointer rounded-lg border bg-card p-2 transition-all hover:border-primary hover:shadow-sm"
        :class="{
          'border-primary ring-2 ring-primary/20': selected,
          'opacity-50': isDragging,
        }"
        @click="handleClick"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
      >
        <div v-if="selectable" class="absolute top-2 right-2 z-10" @click.stop>
          <Checkbox :checked="selected" @update:checked="handleCheckboxChange" />
        </div>

        <div class="aspect-square overflow-hidden rounded-md bg-muted">
          <img
            v-if="file.file_type === 'image' && file.thumbnail_url"
            :src="file.thumbnail_url"
            :alt="file.name"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full w-full items-center justify-center">
            <component :is="fileIcon" class="h-12 w-12 text-muted-foreground" />
          </div>
        </div>

        <div class="mt-2 space-y-1">
          <p class="truncate text-sm font-medium" :title="file.name">
            {{ file.name }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ formattedSize }}
          </p>
        </div>
      </div>

      <!-- List View -->
      <div
        v-else
        draggable="true"
        class="group flex cursor-pointer items-center gap-3 rounded-lg border bg-card p-3 transition-all hover:border-primary hover:shadow-sm"
        :class="{
          'border-primary ring-2 ring-primary/20': selected,
          'opacity-50': isDragging,
        }"
        @click="handleClick"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
      >
        <GripVerticalIcon
          class="h-4 w-4 shrink-0 cursor-grab text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
        />

        <div v-if="selectable" class="shrink-0" @click.stop>
          <Checkbox :checked="selected" @update:checked="handleCheckboxChange" />
        </div>

        <div class="h-10 w-10 shrink-0 overflow-hidden rounded bg-muted">
          <img
            v-if="file.file_type === 'image' && file.thumbnail_url"
            :src="file.thumbnail_url"
            :alt="file.name"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full w-full items-center justify-center">
            <component :is="fileIcon" class="h-5 w-5 text-muted-foreground" />
          </div>
        </div>

        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium" :title="file.name">
            {{ file.name }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ file.mime_type }}
          </p>
        </div>

        <div class="shrink-0 text-right">
          <p class="text-sm text-muted-foreground">{{ formattedSize }}</p>
        </div>
      </div>
    </ContextMenuTrigger>
    <ContextMenuContent>
      <ContextMenuItem @click="emit('viewDetails', file)">
        <InfoIcon class="mr-2 h-4 w-4" />
        View Details
      </ContextMenuItem>
      <ContextMenuItem as="a" :href="file.url" target="_blank">
        <ExternalLinkIcon class="mr-2 h-4 w-4" />
        Open in New Tab
      </ContextMenuItem>
      <ContextMenuSeparator />
      <ContextMenuItem @click="emit('rename', file)">
        <PencilIcon class="mr-2 h-4 w-4" />
        Rename
      </ContextMenuItem>
      <ContextMenuItem @click="emit('move', file)">
        <FolderInputIcon class="mr-2 h-4 w-4" />
        Move to Folder
      </ContextMenuItem>
      <ContextMenuSeparator />
      <ContextMenuItem class="text-destructive" @click="emit('delete', file)">
        <Trash2Icon class="mr-2 h-4 w-4" />
        Delete
      </ContextMenuItem>
    </ContextMenuContent>
  </ContextMenu>
</template>
