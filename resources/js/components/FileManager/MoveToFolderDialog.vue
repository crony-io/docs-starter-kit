<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { update as updateMedia } from '@/routes/admin/media';
import { update as updateFolder } from '@/routes/admin/media/folders';
import type { MediaFile, MediaFolder } from '@/types/media';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronRightIcon, FolderIcon, HomeIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface FolderTreeItem {
  id: number;
  name: string;
  parent_id: number | null;
  children: FolderTreeItem[];
}

interface Props {
  open: boolean;
  file?: MediaFile | null;
  folder?: MediaFolder | null;
  mode?: 'file' | 'folder';
}

const props = withDefaults(defineProps<Props>(), {
  file: null,
  folder: null,
  mode: 'file',
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  moved: [];
}>();

const page = usePage();
const allFolders = computed(() => (page.props.allFolders as FolderTreeItem[]) ?? []);

const selectedFolderId = ref<number | null>(null);
const expandedFolders = ref<Set<number>>(new Set());
const isMoving = ref(false);

const itemName = computed(() => {
  if (props.mode === 'folder') {
    return props.folder?.name ?? '';
  }
  return props.file?.name ?? '';
});

const itemCurrentFolderId = computed(() => {
  if (props.mode === 'folder') {
    return props.folder?.parent_id ?? null;
  }
  return props.file?.folder_id ?? null;
});

const isDisabledFolder = (folderId: number): boolean => {
  if (props.mode === 'folder' && props.folder) {
    if (folderId === props.folder.id) {
      return true;
    }
    const isDescendant = (folders: FolderTreeItem[], targetId: number): boolean => {
      for (const f of folders) {
        if (f.id === targetId) {
          return true;
        }
        if (f.children.length > 0 && isDescendant(f.children, targetId)) {
          return true;
        }
      }
      return false;
    };
    const findFolder = (folders: FolderTreeItem[], id: number): FolderTreeItem | null => {
      for (const f of folders) {
        if (f.id === id) {
          return f;
        }
        if (f.children.length > 0) {
          const found = findFolder(f.children, id);
          if (found) {
            return found;
          }
        }
      }
      return null;
    };
    const sourceFolder = findFolder(allFolders.value, props.folder.id);
    if (sourceFolder && isDescendant(sourceFolder.children, folderId)) {
      return true;
    }
  }
  return false;
};

const toggleExpand = (folderId: number) => {
  if (expandedFolders.value.has(folderId)) {
    expandedFolders.value.delete(folderId);
  } else {
    expandedFolders.value.add(folderId);
  }
};

const selectFolder = (folderId: number | null) => {
  if (folderId !== null && isDisabledFolder(folderId)) {
    return;
  }
  selectedFolderId.value = folderId;
};

const moveItem = () => {
  if (props.mode === 'folder' && props.folder) {
    isMoving.value = true;
    router.patch(
      updateFolder(props.folder.id).url,
      { parent_id: selectedFolderId.value },
      {
        preserveScroll: true,
        onSuccess: () => {
          emit('moved');
          emit('update:open', false);
          isMoving.value = false;
        },
        onError: () => {
          isMoving.value = false;
        },
      },
    );
  } else if (props.file) {
    isMoving.value = true;
    router.patch(
      updateMedia(props.file.id).url,
      { folder_id: selectedFolderId.value },
      {
        preserveScroll: true,
        onSuccess: () => {
          emit('moved');
          emit('update:open', false);
          isMoving.value = false;
        },
        onError: () => {
          isMoving.value = false;
        },
      },
    );
  }
};

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      selectedFolderId.value = itemCurrentFolderId.value;
      expandedFolders.value.clear();
    }
  },
);
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>Move {{ mode === 'folder' ? 'Folder' : 'File' }}</DialogTitle>
        <DialogDescription> Select a destination folder for "{{ itemName }}" </DialogDescription>
      </DialogHeader>

      <div class="h-[300px] overflow-y-auto rounded-md border p-2">
        <button
          type="button"
          class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-accent"
          :class="{ 'bg-accent font-medium': selectedFolderId === null }"
          @click="selectFolder(null)"
        >
          <HomeIcon class="h-4 w-4 text-muted-foreground" />
          <span>Root</span>
        </button>

        <template v-for="folder in allFolders" :key="folder.id">
          <component :is="'div'" class="folder-tree-item">
            <div
              class="flex items-center gap-1 rounded-md px-2 py-1.5 text-sm transition-colors"
              :class="{
                'bg-accent font-medium': selectedFolderId === folder.id,
                'cursor-not-allowed opacity-50': isDisabledFolder(folder.id),
                'cursor-pointer hover:bg-accent': !isDisabledFolder(folder.id),
              }"
            >
              <button
                v-if="folder.children.length > 0"
                type="button"
                class="rounded p-0.5 hover:bg-muted"
                @click.stop="toggleExpand(folder.id)"
              >
                <ChevronRightIcon
                  class="h-4 w-4 transition-transform"
                  :class="{ 'rotate-90': expandedFolders.has(folder.id) }"
                />
              </button>
              <span v-else class="w-5" />
              <button
                type="button"
                class="flex flex-1 items-center gap-2"
                :disabled="isDisabledFolder(folder.id)"
                @click="selectFolder(folder.id)"
              >
                <FolderIcon class="h-4 w-4 text-muted-foreground" />
                <span>{{ folder.name }}</span>
              </button>
            </div>

            <template v-if="expandedFolders.has(folder.id) && folder.children.length > 0">
              <div v-for="child in folder.children" :key="child.id" class="ml-4">
                <div
                  class="flex items-center gap-1 rounded-md px-2 py-1.5 text-sm transition-colors"
                  :class="{
                    'bg-accent font-medium': selectedFolderId === child.id,
                    'cursor-not-allowed opacity-50': isDisabledFolder(child.id),
                    'cursor-pointer hover:bg-accent': !isDisabledFolder(child.id),
                  }"
                >
                  <button
                    v-if="child.children.length > 0"
                    type="button"
                    class="rounded p-0.5 hover:bg-muted"
                    @click.stop="toggleExpand(child.id)"
                  >
                    <ChevronRightIcon
                      class="h-4 w-4 transition-transform"
                      :class="{ 'rotate-90': expandedFolders.has(child.id) }"
                    />
                  </button>
                  <span v-else class="w-5" />
                  <button
                    type="button"
                    class="flex flex-1 items-center gap-2"
                    :disabled="isDisabledFolder(child.id)"
                    @click="selectFolder(child.id)"
                  >
                    <FolderIcon class="h-4 w-4 text-muted-foreground" />
                    <span>{{ child.name }}</span>
                  </button>
                </div>

                <template v-if="expandedFolders.has(child.id) && child.children.length > 0">
                  <div v-for="grandchild in child.children" :key="grandchild.id" class="ml-4">
                    <div
                      class="flex items-center gap-1 rounded-md px-2 py-1.5 text-sm transition-colors"
                      :class="{
                        'bg-accent font-medium': selectedFolderId === grandchild.id,
                        'cursor-not-allowed opacity-50': isDisabledFolder(grandchild.id),
                        'cursor-pointer hover:bg-accent': !isDisabledFolder(grandchild.id),
                      }"
                    >
                      <span class="w-5" />
                      <button
                        type="button"
                        class="flex flex-1 items-center gap-2"
                        :disabled="isDisabledFolder(grandchild.id)"
                        @click="selectFolder(grandchild.id)"
                      >
                        <FolderIcon class="h-4 w-4 text-muted-foreground" />
                        <span>{{ grandchild.name }}</span>
                      </button>
                    </div>
                  </div>
                </template>
              </div>
            </template>
          </component>
        </template>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
        <Button :disabled="isMoving || selectedFolderId === itemCurrentFolderId" @click="moveItem">
          {{ isMoving ? 'Moving...' : 'Move Here' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
