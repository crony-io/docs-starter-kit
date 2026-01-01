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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy as destroyMedia, update as updateMedia } from '@/routes/admin/media';
import type { MediaFile } from '@/types/media';
import { router } from '@inertiajs/vue3';
import { CalendarIcon, FileIcon, HardDriveIcon, UserIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
  open: boolean;
  file: MediaFile | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  updated: [file: MediaFile];
  deleted: [fileId: number];
}>();

const isEditing = ref(false);
const editName = ref('');
const isSaving = ref(false);
const isDeleting = ref(false);

const formattedDate = computed(() => {
  if (!props.file) {
    return '';
  }
  return new Date(props.file.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
});

watch(
  () => props.file,
  (file) => {
    if (file) {
      editName.value = file.name;
      isEditing.value = false;
    }
  },
  { immediate: true },
);

const startEditing = () => {
  if (props.file) {
    editName.value = props.file.name;
    isEditing.value = true;
  }
};

const cancelEditing = () => {
  if (props.file) {
    editName.value = props.file.name;
    isEditing.value = false;
  }
};

const saveChanges = () => {
  if (!props.file || !editName.value.trim()) {
    return;
  }

  isSaving.value = true;
  router.patch(
    updateMedia(props.file.id).url,
    { name: editName.value.trim() },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        emit('updated', { ...props.file!, name: editName.value.trim() });
        isEditing.value = false;
        isSaving.value = false;
      },
      onError: () => {
        isSaving.value = false;
      },
    },
  );
};

const deleteFile = () => {
  if (!props.file) {
    return;
  }

  isDeleting.value = true;
  router.delete(destroyMedia(props.file.id).url, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('deleted', props.file!.id);
      emit('update:open', false);
      isDeleting.value = false;
    },
    onError: () => {
      isDeleting.value = false;
    },
  });
};

const copyUrl = () => {
  if (props.file?.url) {
    navigator.clipboard.writeText(props.file.url);
  }
};
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle>File Details</DialogTitle>
        <DialogDescription>View and edit file information</DialogDescription>
      </DialogHeader>

      <div v-if="file" class="space-y-4">
        <div class="aspect-video overflow-hidden rounded-lg bg-muted">
          <img
            v-if="file.file_type === 'image' && file.url"
            :src="file.url"
            :alt="file.name"
            class="h-full w-full object-contain"
          />
          <div v-else class="flex h-full w-full items-center justify-center">
            <FileIcon class="h-16 w-16 text-muted-foreground" />
          </div>
        </div>

        <div class="space-y-3">
          <div class="space-y-1.5">
            <Label>Name</Label>
            <div v-if="isEditing" class="flex gap-2">
              <Input v-model="editName" class="flex-1" @keyup.enter="saveChanges" />
              <Button size="sm" :disabled="isSaving" @click="saveChanges">
                {{ isSaving ? 'Saving...' : 'Save' }}
              </Button>
              <Button size="sm" variant="outline" @click="cancelEditing">Cancel</Button>
            </div>
            <div v-else class="flex items-center justify-between">
              <p class="text-sm">{{ file.name }}</p>
              <Button size="sm" variant="ghost" @click="startEditing">Edit</Button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <HardDriveIcon class="h-4 w-4" />
              <span>{{ file.human_size }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <FileIcon class="h-4 w-4" />
              <span>{{ file.mime_type }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <CalendarIcon class="h-4 w-4" />
              <span>{{ formattedDate }}</span>
            </div>
            <div v-if="file.uploader" class="flex items-center gap-2 text-sm text-muted-foreground">
              <UserIcon class="h-4 w-4" />
              <span>{{ file.uploader.name }}</span>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label>URL</Label>
            <div class="flex gap-2">
              <Input :model-value="file.url" readonly class="flex-1 text-xs" />
              <Button size="sm" variant="outline" @click="copyUrl">Copy</Button>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter class="flex-row justify-between sm:justify-between">
        <Button variant="destructive" :disabled="isDeleting" @click="deleteFile">
          {{ isDeleting ? 'Deleting...' : 'Delete File' }}
        </Button>
        <Button variant="outline" @click="emit('update:open', false)">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
