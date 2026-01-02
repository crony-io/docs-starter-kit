<script setup lang="ts">
import InputError from '@/components/InputError.vue';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { store } from '@/routes/admin/pages';
import type { Page, PageType } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Book, FolderTree, Loader2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
  potentialParents: Pick<Page, 'id' | 'title' | 'slug' | 'type' | 'parent_id'>[];
  defaultParentId?: number | null;
  defaultType?: PageType;
}

const props = withDefaults(defineProps<Props>(), {
  defaultParentId: null,
  defaultType: 'group',
});

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
  (e: 'created'): void;
}>();

const form = useForm({
  title: '',
  slug: '',
  type: props.defaultType,
  parent_id: props.defaultParentId,
  status: 'draft' as const,
  content: '',
  from_dialog: true,
});

const filteredParents = computed(() => {
  if (form.type === 'navigation') {
    return [];
  }
  if (form.type === 'group') {
    return props.potentialParents.filter((p) => p.type === 'navigation' || p.type === 'group');
  }
  return props.potentialParents;
});

const showParentSelector = computed(() => form.type !== 'navigation');

const generateSlug = (title: string) => {
  return title
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim();
};

watch(
  () => form.title,
  (title) => {
    form.slug = generateSlug(title);
  },
);

watch(open, (isOpen) => {
  if (isOpen) {
    form.reset();
    form.type = props.defaultType;
    form.parent_id = props.defaultParentId;
  }
});

const onTypeChange = (value: unknown) => {
  form.type = String(value) as PageType;
  if (form.type === 'navigation') {
    form.parent_id = null;
  }
};

const onParentChange = (value: unknown) => {
  form.parent_id = value && value !== 'none' ? Number(value) : null;
};

const submit = () => {
  form.post(store().url, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      open.value = false;
      emit('created');
    },
  });
};
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-md" @pointer-down-outside="(e) => e.preventDefault()">
      <DialogHeader>
        <DialogTitle>Quick Create</DialogTitle>
        <DialogDescription> Create a new navigation tab or group quickly. </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <div class="space-y-2">
          <Label>Type</Label>
          <Select :model-value="form.type" @update:model-value="onTypeChange">
            <SelectTrigger>
              <SelectValue placeholder="Select type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="navigation">
                <div class="flex items-center gap-2">
                  <Book class="h-4 w-4" />
                  Navigation Tab
                </div>
              </SelectItem>
              <SelectItem value="group">
                <div class="flex items-center gap-2">
                  <FolderTree class="h-4 w-4" />
                  Group
                </div>
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="form.errors.type" />
        </div>

        <div class="space-y-2">
          <Label for="quick-title">Title</Label>
          <Input
            id="quick-title"
            v-model="form.title"
            placeholder="Enter title..."
            :disabled="form.processing"
            autofocus
          />
          <InputError :message="form.errors.title" />
        </div>

        <div class="space-y-2">
          <Label for="quick-slug">Slug</Label>
          <Input
            id="quick-slug"
            v-model="form.slug"
            placeholder="auto-generated-slug"
            :disabled="form.processing"
          />
          <InputError :message="form.errors.slug" />
        </div>

        <div v-if="showParentSelector" class="space-y-2">
          <Label>Parent</Label>
          <Select
            :model-value="form.parent_id?.toString() ?? 'none'"
            @update:model-value="onParentChange"
          >
            <SelectTrigger>
              <SelectValue placeholder="Select parent" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">No parent (root level)</SelectItem>
              <SelectItem
                v-for="parent in filteredParents"
                :key="parent.id"
                :value="parent.id.toString()"
              >
                <span :class="{ 'pl-4': parent.parent_id }">
                  {{ parent.title }}
                  <span class="text-xs text-muted-foreground">({{ parent.type }})</span>
                </span>
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="form.errors.parent_id" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="open = false" :disabled="form.processing">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing || !form.title.trim()">
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            Create
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
