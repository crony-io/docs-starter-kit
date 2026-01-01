<script setup lang="ts">
import CreateFolderDialog from '@/components/FileManager/CreateFolderDialog.vue';
import FileDetailsDialog from '@/components/FileManager/FileDetailsDialog.vue';
import FileManagerFolderItem from '@/components/FileManager/FileManagerFolderItem.vue';
import FileManagerItem from '@/components/FileManager/FileManagerItem.vue';
import FileManagerToolbar from '@/components/FileManager/FileManagerToolbar.vue';
import FileManagerUploader from '@/components/FileManager/FileManagerUploader.vue';
import MoveToFolderDialog from '@/components/FileManager/MoveToFolderDialog.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
  bulkDestroy,
  destroy as destroyMedia,
  index as mediaIndex,
  store as mediaStore,
  update as updateMedia,
} from '@/routes/admin/media';
import { destroy as destroyFolder, update as updateFolder } from '@/routes/admin/media/folders';
import type { MediaFile, MediaFilters, MediaFolder } from '@/types/media';
import { router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
  selectionMode?: 'single' | 'multiple';
  acceptTypes?: ('image' | 'document' | 'video' | 'audio')[];
  initialSelection?: MediaFile[];
}

const props = withDefaults(defineProps<Props>(), {
  selectionMode: 'single',
  acceptTypes: () => ['image', 'document', 'video', 'audio'],
});

const emit = defineEmits<{
  select: [files: MediaFile[]];
}>();

const files = ref<MediaFile[]>([]);
const folders = ref<MediaFolder[]>([]);
const currentFolder = ref<MediaFolder | null>(null);
const selectedFiles = ref<MediaFile[]>(props.initialSelection ?? []);
const filters = ref<MediaFilters>({});
const viewMode = ref<'grid' | 'list'>('grid');
const isLoading = ref(false);
const showUploader = ref(false);
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
});

const uploaderRef = ref<InstanceType<typeof FileManagerUploader>>();

const showCreateFolderDialog = ref(false);
const showFileDetailsDialog = ref(false);
const showMoveDialog = ref(false);
const selectedFileForAction = ref<MediaFile | null>(null);
const selectedFolderForAction = ref<MediaFolder | null>(null);
const moveDialogMode = ref<'file' | 'folder'>('file');

const selectedIds = computed(() => new Set(selectedFiles.value.map((f) => f.id)));

interface FetchFilesResponse {
  files: {
    data: MediaFile[];
    current_page: number;
    last_page: number;
    total: number;
  };
  folders: MediaFolder[];
  currentFolder: MediaFolder | null;
}

const fetchFiles = (page = 1) => {
  isLoading.value = true;

  const query: Record<string, string> = { page: String(page) };

  if (filters.value.folder_id) {
    query.folder_id = String(filters.value.folder_id);
  }
  if (filters.value.search) {
    query.search = filters.value.search;
  }
  if (filters.value.type) {
    query.type = filters.value.type;
  }

  router.get(
    mediaIndex.url({ query }),
    {},
    {
      preserveState: true,
      preserveScroll: true,
      only: ['files', 'folders', 'currentFolder'],
      onSuccess: (page) => {
        const props = page.props as unknown as FetchFilesResponse;
        files.value = props.files?.data ?? [];
        folders.value = props.folders ?? [];
        currentFolder.value = props.currentFolder ?? null;
        pagination.value = {
          currentPage: props.files?.current_page ?? 1,
          lastPage: props.files?.last_page ?? 1,
          total: props.files?.total ?? 0,
        };
        isLoading.value = false;
      },
      onError: () => {
        console.error('Failed to fetch files');
        isLoading.value = false;
      },
    },
  );
};

const navigateToFolder = (folderId: number | null) => {
  filters.value = { ...filters.value, folder_id: folderId ?? undefined };
};

const navigateUp = () => {
  if (currentFolder.value?.parent_id) {
    navigateToFolder(currentFolder.value.parent_id);
  } else {
    navigateToFolder(null);
  }
};

const handleFileSelect = (file: MediaFile) => {
  if (props.selectionMode === 'single') {
    selectedFiles.value = [file];
    emit('select', [file]);
  }
};

const handleFileToggle = (file: MediaFile) => {
  const index = selectedFiles.value.findIndex((f) => f.id === file.id);
  if (index === -1) {
    if (props.selectionMode === 'single') {
      selectedFiles.value = [file];
    } else {
      selectedFiles.value.push(file);
    }
  } else {
    selectedFiles.value.splice(index, 1);
  }
};

const handleUpload = (uploadFiles: File[]) => {
  let uploadedCount = 0;
  const totalFiles = uploadFiles.length;

  for (const file of uploadFiles) {
    const formData: Record<string, File | string> = { file };
    if (filters.value.folder_id) {
      formData.folder_id = String(filters.value.folder_id);
    }

    router.post(mediaStore().url, formData, {
      forceFormData: true,
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        uploadedCount++;
        if (uploadedCount === totalFiles) {
          fetchFiles(pagination.value.currentPage);
          uploaderRef.value?.clearQueue();
          showUploader.value = false;
        }
      },
    });
  }
};

const handleDeleteSelected = () => {
  if (selectedFiles.value.length === 0) {
    return;
  }

  const count = selectedFiles.value.length;
  const idsToDelete = selectedFiles.value.map((f) => f.id);

  router.post(
    bulkDestroy().url,
    { ids: idsToDelete },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        files.value = files.value.filter((f) => !selectedIds.value.has(f.id));
        selectedFiles.value = [];
        pagination.value.total -= count;
      },
    },
  );
};

const openFileDetails = (file: MediaFile) => {
  selectedFileForAction.value = file;
  showFileDetailsDialog.value = true;
};

const openMoveDialog = (file: MediaFile) => {
  selectedFileForAction.value = file;
  selectedFolderForAction.value = null;
  moveDialogMode.value = 'file';
  showMoveDialog.value = true;
};

const openMoveFolderDialog = (folder: MediaFolder) => {
  selectedFolderForAction.value = folder;
  selectedFileForAction.value = null;
  moveDialogMode.value = 'folder';
  showMoveDialog.value = true;
};

const handleDeleteFile = (file: MediaFile) => {
  router.delete(destroyMedia(file.id).url, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      files.value = files.value.filter((f) => f.id !== file.id);
      pagination.value.total--;
    },
  });
};

const handleFileUpdated = (updatedFile: MediaFile) => {
  const index = files.value.findIndex((f) => f.id === updatedFile.id);
  if (index !== -1) {
    files.value[index] = updatedFile;
  }
};

const handleFileDeleted = (fileId: number) => {
  files.value = files.value.filter((f) => f.id !== fileId);
  pagination.value.total--;
};

const handleItemMoved = () => {
  fetchFiles(pagination.value.currentPage);
};

const handleDropFileOnFolder = (folder: MediaFolder, fileId: number) => {
  router.patch(
    updateMedia(fileId).url,
    { folder_id: folder.id },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        files.value = files.value.filter((f) => f.id !== fileId);
      },
    },
  );
};

const handleDropFolderOnFolder = (targetFolder: MediaFolder, sourceFolderId: number) => {
  if (targetFolder.id === sourceFolderId) {
    return;
  }

  router.patch(
    updateFolder(sourceFolderId).url,
    { parent_id: targetFolder.id },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        folders.value = folders.value.filter((f) => f.id !== sourceFolderId);
      },
    },
  );
};

const handleFolderCreated = () => {
  fetchFiles(pagination.value.currentPage);
};

const handleDeleteFolder = (folder: MediaFolder) => {
  router.delete(destroyFolder(folder.id).url, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      folders.value = folders.value.filter((f) => f.id !== folder.id);
    },
  });
};

const goToPage = (page: number) => {
  if (page >= 1 && page <= pagination.value.lastPage) {
    fetchFiles(page);
  }
};

watch(filters, () => fetchFiles(1), { deep: true });

fetchFiles();

defineExpose({
  refresh: () => fetchFiles(pagination.value.currentPage),
  getSelected: () => selectedFiles.value,
});
</script>

<template>
  <div class="flex h-full flex-col">
    <FileManagerToolbar
      :filters="filters"
      :selected-count="selectedFiles.length"
      :view-mode="viewMode"
      @update:filters="filters = $event"
      @update:view-mode="viewMode = $event"
      @upload="showUploader = !showUploader"
      @create-folder="showCreateFolderDialog = true"
      @delete-selected="handleDeleteSelected"
    />

    <div v-if="showUploader" class="border-b p-4">
      <FileManagerUploader ref="uploaderRef" @upload="handleUpload" @close="showUploader = false" />
    </div>

    <div class="flex-1 overflow-auto p-4">
      <div v-if="currentFolder" class="mb-4 flex items-center gap-2">
        <Button variant="ghost" size="sm" @click="navigateUp">
          <ChevronLeftIcon class="mr-1 h-4 w-4" />
          Back
        </Button>
        <span class="text-sm text-muted-foreground">{{ currentFolder.name }}</span>
      </div>

      <div
        v-if="isLoading"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
      >
        <Skeleton v-for="i in 12" :key="i" class="aspect-square rounded-lg" />
      </div>

      <div
        v-else-if="folders.length === 0 && files.length === 0"
        class="flex h-full items-center justify-center"
      >
        <div class="text-center">
          <p class="text-muted-foreground">No files found</p>
          <Button variant="outline" class="mt-4" @click="showUploader = true">
            Upload your first file
          </Button>
        </div>
      </div>

      <!-- Grid View -->
      <div
        v-else-if="viewMode === 'grid'"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
      >
        <FileManagerFolderItem
          v-for="folder in folders"
          :key="`folder-${folder.id}`"
          :folder="folder"
          view-mode="grid"
          @open="navigateToFolder(folder.id)"
          @move="openMoveFolderDialog"
          @delete="handleDeleteFolder"
          @drop-file="handleDropFileOnFolder"
          @drop-folder="handleDropFolderOnFolder"
        />

        <FileManagerItem
          v-for="file in files"
          :key="file.id"
          :file="file"
          :selected="selectedIds.has(file.id)"
          :selectable="selectionMode === 'multiple'"
          view-mode="grid"
          @select="handleFileSelect"
          @toggle="handleFileToggle"
          @view-details="openFileDetails"
          @rename="openFileDetails"
          @move="openMoveDialog"
          @delete="handleDeleteFile"
        />
      </div>

      <!-- List View -->
      <div v-else class="flex flex-col gap-2">
        <FileManagerFolderItem
          v-for="folder in folders"
          :key="`folder-${folder.id}`"
          :folder="folder"
          view-mode="list"
          @open="navigateToFolder(folder.id)"
          @move="openMoveFolderDialog"
          @delete="handleDeleteFolder"
          @drop-file="handleDropFileOnFolder"
          @drop-folder="handleDropFolderOnFolder"
        />

        <FileManagerItem
          v-for="file in files"
          :key="file.id"
          :file="file"
          :selected="selectedIds.has(file.id)"
          :selectable="selectionMode === 'multiple'"
          view-mode="list"
          @select="handleFileSelect"
          @toggle="handleFileToggle"
          @view-details="openFileDetails"
          @rename="openFileDetails"
          @move="openMoveDialog"
          @delete="handleDeleteFile"
        />
      </div>
    </div>

    <div
      v-if="pagination.lastPage > 1"
      class="flex items-center justify-between border-t px-4 py-3"
    >
      <p class="text-sm text-muted-foreground">
        Showing {{ files.length }} of {{ pagination.total }} files
      </p>
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="icon"
          :disabled="pagination.currentPage <= 1"
          @click="goToPage(pagination.currentPage - 1)"
        >
          <ChevronLeftIcon class="h-4 w-4" />
        </Button>
        <span class="text-sm">
          Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
        </span>
        <Button
          variant="outline"
          size="icon"
          :disabled="pagination.currentPage >= pagination.lastPage"
          @click="goToPage(pagination.currentPage + 1)"
        >
          <ChevronRightIcon class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <CreateFolderDialog
      v-model:open="showCreateFolderDialog"
      :parent-folder="currentFolder"
      @created="handleFolderCreated"
    />

    <FileDetailsDialog
      v-model:open="showFileDetailsDialog"
      :file="selectedFileForAction"
      @updated="handleFileUpdated"
      @deleted="handleFileDeleted"
    />

    <MoveToFolderDialog
      v-model:open="showMoveDialog"
      :file="selectedFileForAction"
      :folder="selectedFolderForAction"
      :mode="moveDialogMode"
      @moved="handleItemMoved"
    />
  </div>
</template>
