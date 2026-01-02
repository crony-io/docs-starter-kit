<script setup lang="ts">
import FileManagerBrowser from '@/components/FileManager/FileManagerBrowser.vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import type { MediaFile } from '@/types/media';
import { ref, watch } from 'vue';

interface Props {
  open: boolean;
  title?: string;
  description?: string;
  selectionMode?: 'single' | 'multiple';
  acceptTypes?: ('image' | 'document' | 'video' | 'audio')[];
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Select File',
  description: 'Browse and select files from your media library',
  selectionMode: 'single',
  acceptTypes: () => ['image', 'document', 'video', 'audio'],
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  confirm: [files: MediaFile[]];
  cancel: [];
}>();

const browserRef = ref<InstanceType<typeof FileManagerBrowser>>();
const selectedFiles = ref<MediaFile[]>([]);

const handleSelect = (files: MediaFile[]) => {
  selectedFiles.value = files;
};

const handleConfirm = () => {
  emit('confirm', selectedFiles.value);
  emit('update:open', false);
};

const handleCancel = () => {
  emit('cancel');
  emit('update:open', false);
};

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      selectedFiles.value = [];
    }
  },
);
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="flex h-[80vh] max-w-5xl flex-col p-0">
      <DialogHeader class="border-b px-6 py-4">
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>{{ description }}</DialogDescription>
      </DialogHeader>

      <div class="flex-1 overflow-hidden">
        <FileManagerBrowser
          ref="browserRef"
          :selection-mode="selectionMode"
          :accept-types="acceptTypes"
          embedded
          @select="handleSelect"
        />
      </div>

      <DialogFooter class="border-t px-6 py-4">
        <Button variant="outline" @click="handleCancel">Cancel</Button>
        <Button :disabled="selectedFiles.length === 0" @click="handleConfirm">
          Select {{ selectedFiles.length > 0 ? `(${selectedFiles.length})` : '' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
