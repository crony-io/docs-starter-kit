<script setup lang="ts">
import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuSeparator,
  ContextMenuTrigger,
} from '@/components/ui/context-menu';
import type { MediaFolder } from '@/types/media';
import {
  FolderIcon,
  FolderOpenIcon,
  GripVerticalIcon,
  MoveIcon,
  Trash2Icon,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
  folder: MediaFolder;
  viewMode?: 'grid' | 'list';
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'grid',
});

const emit = defineEmits<{
  open: [folder: MediaFolder];
  move: [folder: MediaFolder];
  delete: [folder: MediaFolder];
  dropFile: [targetFolder: MediaFolder, fileId: number];
  dropFolder: [targetFolder: MediaFolder, sourceFolderId: number];
  dragStart: [folder: MediaFolder];
  dragEnd: [];
}>();

const isDragOver = ref(false);
const isDragging = ref(false);

const handleDragStart = (e: DragEvent) => {
  isDragging.value = true;
  e.dataTransfer?.setData(
    'application/json',
    JSON.stringify({ type: 'folder', id: props.folder.id }),
  );
  e.dataTransfer!.effectAllowed = 'move';
  emit('dragStart', props.folder);
};

const handleDragEnd = () => {
  isDragging.value = false;
  emit('dragEnd');
};

const handleDragOver = (e: DragEvent) => {
  e.preventDefault();

  // Check if we're dragging something valid
  try {
    const types = e.dataTransfer?.types || [];
    if (types.includes('application/json')) {
      e.dataTransfer!.dropEffect = 'move';
      isDragOver.value = true;
    }
  } catch {
    // Ignore
  }
};

const handleDragLeave = (e: DragEvent) => {
  // Only set to false if we're actually leaving this element
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
  const x = e.clientX;
  const y = e.clientY;

  if (x < rect.left || x >= rect.right || y < rect.top || y >= rect.bottom) {
    isDragOver.value = false;
  }
};

const handleDrop = (e: DragEvent) => {
  e.preventDefault();
  e.stopPropagation();
  isDragOver.value = false;

  try {
    const data = JSON.parse(e.dataTransfer?.getData('application/json') || '{}');

    if (data.type === 'file' && data.id) {
      emit('dropFile', props.folder, data.id);
    } else if (data.type === 'folder' && data.id) {
      // Prevent dropping folder into itself
      if (data.id !== props.folder.id) {
        emit('dropFolder', props.folder, data.id);
      }
    }
  } catch {
    console.error('Invalid drop data');
  }
};
</script>

<template>
  <ContextMenu>
    <ContextMenuTrigger as-child>
      <!-- Grid View -->
      <div
        v-if="viewMode === 'grid'"
        draggable="true"
        class="group cursor-pointer rounded-lg border bg-card p-2 transition-all hover:border-primary hover:shadow-sm"
        :class="{
          'border-primary bg-primary/10 ring-2 ring-primary': isDragOver,
          'opacity-50': isDragging,
        }"
        @click="emit('open', folder)"
        @dblclick="emit('open', folder)"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
      >
        <div class="flex aspect-square items-center justify-center rounded-md bg-muted">
          <FolderIcon
            v-if="!isDragOver"
            class="h-12 w-12 text-muted-foreground transition-colors group-hover:text-primary"
          />
          <FolderOpenIcon v-else class="h-12 w-12 text-primary" />
        </div>
        <div class="mt-2">
          <p class="truncate text-sm font-medium">{{ folder.name }}</p>
        </div>
      </div>

      <!-- List View -->
      <div
        v-else
        draggable="true"
        class="group flex cursor-pointer items-center gap-3 rounded-lg border bg-card p-3 transition-all hover:border-primary hover:shadow-sm"
        :class="{
          'border-primary bg-primary/10 ring-2 ring-primary': isDragOver,
          'opacity-50': isDragging,
        }"
        @click="emit('open', folder)"
        @dblclick="emit('open', folder)"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
      >
        <GripVerticalIcon
          class="h-4 w-4 shrink-0 cursor-grab text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
        />

        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted">
          <FolderIcon
            v-if="!isDragOver"
            class="h-5 w-5 text-muted-foreground transition-colors group-hover:text-primary"
          />
          <FolderOpenIcon v-else class="h-5 w-5 text-primary" />
        </div>

        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium">{{ folder.name }}</p>
          <p class="text-xs text-muted-foreground">Folder</p>
        </div>
      </div>
    </ContextMenuTrigger>
    <ContextMenuContent>
      <ContextMenuItem @click="emit('open', folder)">
        <FolderOpenIcon class="mr-2 h-4 w-4" />
        Open
      </ContextMenuItem>
      <ContextMenuItem @click="emit('move', folder)">
        <MoveIcon class="mr-2 h-4 w-4" />
        Move to...
      </ContextMenuItem>
      <ContextMenuSeparator />
      <ContextMenuItem class="text-destructive" @click="emit('delete', folder)">
        <Trash2Icon class="mr-2 h-4 w-4" />
        Delete Folder
      </ContextMenuItem>
    </ContextMenuContent>
  </ContextMenu>
</template>
