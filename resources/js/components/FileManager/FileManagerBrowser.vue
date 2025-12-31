<script setup lang="ts">
import FileManagerItem from '@/components/FileManager/FileManagerItem.vue';
import FileManagerToolbar from '@/components/FileManager/FileManagerToolbar.vue';
import FileManagerUploader from '@/components/FileManager/FileManagerUploader.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { bulkDestroy, index as mediaIndex, store as mediaStore } from '@/routes/admin/media';
import type { MediaFile, MediaFilters, MediaFolder } from '@/types/media';
import axios from 'axios';
import { ChevronLeftIcon, ChevronRightIcon, FolderIcon } from 'lucide-vue-next';
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

const selectedIds = computed(() => new Set(selectedFiles.value.map((f) => f.id)));

const fetchFiles = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = new URLSearchParams();
    params.set('page', String(page));

    if (filters.value.folder_id) {
      params.set('folder_id', String(filters.value.folder_id));
    }
    if (filters.value.search) {
      params.set('search', filters.value.search);
    }
    if (filters.value.type) {
      params.set('type', filters.value.type);
    }

    const response = await axios.get(mediaIndex.url({ query: Object.fromEntries(params) }), {
      headers: { Accept: 'application/json' },
    });

    files.value = response.data.files.data;
    folders.value = response.data.folders ?? [];
    currentFolder.value = response.data.currentFolder ?? null;
    pagination.value = {
      currentPage: response.data.files.current_page,
      lastPage: response.data.files.last_page,
      total: response.data.files.total,
    };
  } catch (error) {
    console.error('Failed to fetch files:', error);
  } finally {
    isLoading.value = false;
  }
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

const handleUpload = async (uploadFiles: File[]) => {
  for (const file of uploadFiles) {
    const formData = new FormData();
    formData.append('file', file);
    if (filters.value.folder_id) {
      formData.append('folder_id', String(filters.value.folder_id));
    }

    try {
      const response = await axios.post(mediaStore().url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      files.value.unshift(response.data.file);
      pagination.value.total++;
    } catch (error) {
      console.error('Upload failed:', error);
    }
  }

  uploaderRef.value?.clearQueue();
  showUploader.value = false;
};

const handleDeleteSelected = async () => {
  if (selectedFiles.value.length === 0) {
    return;
  }

  try {
    await axios.post(bulkDestroy().url, {
      ids: selectedFiles.value.map((f) => f.id),
    });

    files.value = files.value.filter((f) => !selectedIds.value.has(f.id));
    selectedFiles.value = [];
    pagination.value.total -= selectedFiles.value.length;
  } catch (error) {
    console.error('Delete failed:', error);
  }
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

      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
        <div
          v-for="folder in folders"
          :key="`folder-${folder.id}`"
          class="group cursor-pointer rounded-lg border bg-card p-2 transition-all hover:border-primary hover:shadow-sm"
          @click="navigateToFolder(folder.id)"
        >
          <div class="flex aspect-square items-center justify-center rounded-md bg-muted">
            <FolderIcon class="h-12 w-12 text-muted-foreground" />
          </div>
          <div class="mt-2">
            <p class="truncate text-sm font-medium">{{ folder.name }}</p>
          </div>
        </div>

        <FileManagerItem
          v-for="file in files"
          :key="file.id"
          :file="file"
          :selected="selectedIds.has(file.id)"
          :selectable="selectionMode === 'multiple'"
          @select="handleFileSelect"
          @toggle="handleFileToggle"
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
  </div>
</template>
