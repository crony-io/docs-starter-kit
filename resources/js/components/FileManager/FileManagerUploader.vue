<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { AlertCircleIcon, CheckCircleIcon, UploadCloudIcon, XIcon } from 'lucide-vue-next';
import { ref } from 'vue';

interface UploadFile {
  id: string;
  file: File;
  progress: number;
  status: 'pending' | 'uploading' | 'success' | 'error';
  error?: string;
}

const emit = defineEmits<{
  upload: [files: File[]];
  close: [];
}>();

const isDragging = ref(false);
const uploadQueue = ref<UploadFile[]>([]);
const fileInputRef = ref<HTMLInputElement>();

const handleDragOver = (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = () => {
  isDragging.value = false;
};

const handleDrop = (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = false;

  const files = Array.from(e.dataTransfer?.files ?? []);
  if (files.length > 0) {
    addFilesToQueue(files);
  }
};

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const files = Array.from(target.files ?? []);
  if (files.length > 0) {
    addFilesToQueue(files);
  }
  target.value = '';
};

const addFilesToQueue = (files: File[]) => {
  const newFiles: UploadFile[] = files.map((file) => ({
    id: crypto.randomUUID(),
    file,
    progress: 0,
    status: 'pending',
  }));

  uploadQueue.value.push(...newFiles);
  emit('upload', files);
};

const removeFromQueue = (id: string) => {
  uploadQueue.value = uploadQueue.value.filter((f) => f.id !== id);
};

const openFilePicker = () => {
  fileInputRef.value?.click();
};

const formatFileSize = (bytes: number): string => {
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  let size = bytes;

  while (size > 1024 && i < units.length - 1) {
    size /= 1024;
    i++;
  }

  return `${size.toFixed(1)} ${units[i]}`;
};

defineExpose({
  updateProgress: (fileId: string, progress: number) => {
    const file = uploadQueue.value.find((f) => f.id === fileId);
    if (file) {
      file.progress = progress;
      file.status = 'uploading';
    }
  },
  setSuccess: (fileId: string) => {
    const file = uploadQueue.value.find((f) => f.id === fileId);
    if (file) {
      file.progress = 100;
      file.status = 'success';
    }
  },
  setError: (fileId: string, error: string) => {
    const file = uploadQueue.value.find((f) => f.id === fileId);
    if (file) {
      file.status = 'error';
      file.error = error;
    }
  },
  clearQueue: () => {
    uploadQueue.value = [];
  },
});
</script>

<template>
  <div class="space-y-4">
    <div
      class="flex min-h-[200px] cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 transition-colors"
      :class="
        isDragging
          ? 'border-primary bg-primary/5'
          : 'border-muted-foreground/25 hover:border-primary/50'
      "
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      @drop="handleDrop"
      @click="openFilePicker"
    >
      <UploadCloudIcon class="mb-4 h-12 w-12 text-muted-foreground" />
      <p class="mb-2 text-sm font-medium">Drag and drop files here</p>
      <p class="text-xs text-muted-foreground">or click to browse</p>
      <p class="mt-2 text-xs text-muted-foreground">Max file size: 10MB</p>
    </div>

    <input
      ref="fileInputRef"
      type="file"
      multiple
      accept="image/*,.pdf,.doc,.docx,.mp4,.webm,.mp3,.wav"
      class="hidden"
      @change="handleFileSelect"
    />

    <div v-if="uploadQueue.length > 0" class="space-y-2">
      <p class="text-sm font-medium">Upload Queue</p>
      <div class="max-h-[200px] space-y-2 overflow-y-auto">
        <div
          v-for="item in uploadQueue"
          :key="item.id"
          class="flex items-center gap-3 rounded-md border p-2"
        >
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">{{ item.file.name }}</p>
            <p class="text-xs text-muted-foreground">{{ formatFileSize(item.file.size) }}</p>
            <Progress
              v-if="item.status === 'uploading'"
              :model-value="item.progress"
              class="mt-1 h-1"
            />
          </div>

          <div class="flex items-center gap-2">
            <CheckCircleIcon v-if="item.status === 'success'" class="h-5 w-5 text-green-500" />
            <AlertCircleIcon v-else-if="item.status === 'error'" class="h-5 w-5 text-destructive" />
            <Button
              v-if="item.status !== 'uploading'"
              variant="ghost"
              size="icon"
              class="h-6 w-6"
              @click="removeFromQueue(item.id)"
            >
              <XIcon class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
