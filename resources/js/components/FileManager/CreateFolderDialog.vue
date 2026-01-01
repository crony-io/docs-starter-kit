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
import { store as storeFolder } from '@/routes/admin/media/folders';
import type { MediaFolder } from '@/types/media';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
  open: boolean;
  parentFolder?: MediaFolder | null;
}

const props = withDefaults(defineProps<Props>(), {
  parentFolder: null,
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  created: [];
}>();

const folderName = ref('');
const isCreating = ref(false);
const error = ref('');

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      folderName.value = '';
      error.value = '';
    }
  },
);

const createFolder = () => {
  if (!folderName.value.trim()) {
    error.value = 'Folder name is required';
    return;
  }

  isCreating.value = true;
  error.value = '';

  router.post(
    storeFolder().url,
    {
      name: folderName.value.trim(),
      parent_id: props.parentFolder?.id ?? null,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        emit('created');
        emit('update:open', false);
        isCreating.value = false;
      },
      onError: (errors) => {
        if (errors.name) {
          error.value = errors.name;
        } else {
          error.value = 'Failed to create folder';
        }
        isCreating.value = false;
      },
    },
  );
};
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>Create Folder</DialogTitle>
        <DialogDescription>
          <template v-if="parentFolder">
            Create a new folder inside "{{ parentFolder.name }}"
          </template>
          <template v-else> Create a new folder in the root directory </template>
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <Label for="folder-name">Folder Name</Label>
          <Input
            id="folder-name"
            v-model="folderName"
            placeholder="Enter folder name"
            @keyup.enter="createFolder"
          />
          <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
        <Button :disabled="isCreating || !folderName.trim()" @click="createFolder">
          {{ isCreating ? 'Creating...' : 'Create Folder' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
