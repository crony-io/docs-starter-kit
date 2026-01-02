<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import IconPicker from '@/components/IconPicker.vue';
import InputError from '@/components/InputError.vue';
import PageEditor from '@/components/PageEditor/PageEditor.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
  edit,
  index as pagesIndex,
  publish,
  restoreVersion as restoreVersionRoute,
  unpublish,
  update,
} from '@/routes/admin/pages';
import type { BreadcrumbItem, Page, PageType, PageVersion } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useDebounceFn, useLocalStorage } from '@vueuse/core';
import {
  ArrowLeft,
  Book,
  Check,
  Eye,
  EyeOff,
  FileText,
  FolderTree,
  Globe,
  History,
  RotateCcw,
  Save,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
  page: Page & { versions?: PageVersion[]; full_path?: string };
  navigationTabs: Pick<Page, 'id' | 'title' | 'slug'>[];
  potentialParents: Pick<Page, 'id' | 'title' | 'slug' | 'type' | 'parent_id'>[];
}

const props = defineProps<Props>();

const pageTypes = [
  { value: 'navigation', label: 'Navigation Tab', icon: Book, description: 'Top-level tab' },
  { value: 'group', label: 'Group', icon: FolderTree, description: 'Sidebar group/section' },
  {
    value: 'document',
    label: 'Document',
    icon: FileText,
    description: 'Content page with markdown',
  },
];

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Pages', href: pagesIndex().url },
  { title: props.page.title, href: edit(props.page.id).url },
];

const form = useForm({
  title: props.page.title,
  slug: props.page.slug,
  type: props.page.type,
  icon: props.page.icon ?? '',
  content: props.page.content ?? '',
  status: props.page.status,
  parent_id: props.page.parent_id,
  is_default: props.page.is_default,
  is_expanded: props.page.is_expanded,
  seo_title: props.page.seo_title ?? '',
  seo_description: props.page.seo_description ?? '',
});

const showContentEditor = computed(() => form.type === 'document');
const showParentSelector = computed(() => form.type !== 'navigation');

const isPublishing = ref(false);
const isUnpublishing = ref(false);

const submit = () => {
  form.submit(update(props.page.id));
};

const quickPublish = () => {
  isPublishing.value = true;
  router.post(
    publish(props.page.id).url,
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        isPublishing.value = false;
      },
    },
  );
};

const quickUnpublish = () => {
  isUnpublishing.value = true;
  router.post(
    unpublish(props.page.id).url,
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        isUnpublishing.value = false;
      },
    },
  );
};

const onStatusChange = (value: unknown) => {
  form.status = String(value) as 'draft' | 'published' | 'archived';
};

const onTypeChange = (value: unknown) => {
  form.type = String(value) as PageType;
  if (form.type === 'navigation') {
    form.parent_id = null;
  }
};

const onParentChange = (value: unknown) => {
  form.parent_id = value && value !== 'none' ? Number(value) : null;
};

const filteredParents = computed(() => {
  if (form.type === 'navigation') {
    return [];
  }
  if (form.type === 'group') {
    return props.potentialParents.filter((p) => p.type === 'navigation' || p.type === 'group');
  }
  return props.potentialParents;
});

const getParentPath = (parentId: number | null): string => {
  if (!parentId) {
    return '';
  }
  const segments: string[] = [];
  let currentId: number | null = parentId;

  while (currentId) {
    const parent = props.potentialParents.find((p) => p.id === currentId);
    if (!parent) {
      break;
    }
    segments.unshift(parent.slug);
    currentId = parent.parent_id;
  }

  return segments.length > 0 ? segments.join('/') + '/' : '';
};

const parentPath = computed(() => getParentPath(form.parent_id));

const fullPath = computed(() => parentPath.value + form.slug);

const canSubmit = computed(() => form.title.trim().length > 0 && form.isDirty);

const autoSaveEnabled = ref(true);
const lastSaved = ref<Date | null>(null);
const autoSaveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');
const restoringVersionId = ref<number | null>(null);

const restoreVersion = (versionId: number) => {
  restoringVersionId.value = versionId;
  router.post(
    restoreVersionRoute({ page: props.page.id, versionId }).url,
    {},
    {
      preserveScroll: false,
      preserveState: false,
      onFinish: () => {
        restoringVersionId.value = null;
      },
    },
  );
};

const localStorageKey = `page-draft-${props.page.id}`;
const savedDraft = useLocalStorage(localStorageKey, '');

const autoSave = useDebounceFn(() => {
  if (!autoSaveEnabled.value || !form.isDirty || form.processing) {
    return;
  }

  savedDraft.value = JSON.stringify({
    title: form.title,
    slug: form.slug,
    content: form.content,
    seo_title: form.seo_title,
    seo_description: form.seo_description,
    savedAt: new Date().toISOString(),
  });

  autoSaveStatus.value = 'saving';

  form.put(update(props.page.id).url, {
    preserveScroll: true,
    onSuccess: () => {
      lastSaved.value = new Date();
      autoSaveStatus.value = 'saved';
      savedDraft.value = '';
      setTimeout(() => {
        if (autoSaveStatus.value === 'saved') {
          autoSaveStatus.value = 'idle';
        }
      }, 3000);
    },
    onError: () => {
      autoSaveStatus.value = 'error';
    },
  });
}, 30000);

watch(
  () => [form.title, form.slug, form.content, form.seo_title, form.seo_description],
  () => {
    if (autoSaveEnabled.value && form.isDirty) {
      autoSave();
    }
  },
);

const handleBeforeUnload = (e: BeforeUnloadEvent) => {
  if (form.isDirty) {
    e.preventDefault();
    e.returnValue = '';
  }
};

const restoreDraft = () => {
  if (savedDraft.value) {
    try {
      const draft = JSON.parse(savedDraft.value);
      form.title = draft.title ?? form.title;
      form.slug = draft.slug ?? form.slug;
      form.content = draft.content ?? form.content;
      form.seo_title = draft.seo_title ?? form.seo_title;
      form.seo_description = draft.seo_description ?? form.seo_description;
    } catch {
      savedDraft.value = '';
    }
  }
};

const discardDraft = () => {
  savedDraft.value = '';
};

const hasDraft = computed(() => {
  if (!savedDraft.value) {
    return false;
  }
  try {
    const draft = JSON.parse(savedDraft.value);
    return draft.savedAt && new Date(draft.savedAt) > new Date(props.page.updated_at);
  } catch {
    return false;
  }
});

onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});

useKeyboardShortcuts([
  {
    key: 's',
    ctrl: true,
    handler: () => {
      if (canSubmit.value && !form.processing) {
        submit();
      }
    },
  },
]);

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<template>
  <Head :title="`Edit: ${page.title}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6 pb-20">
      <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Heading :title="page.title" description="Edit documentation page" />
          <StatusBadge :status="page.status" />
          <div
            v-if="autoSaveStatus === 'saved'"
            class="flex items-center gap-1 text-sm text-green-600"
          >
            <Check class="h-4 w-4" />
            <span>Saved</span>
          </div>
          <div v-else-if="autoSaveStatus === 'saving'" class="text-sm text-muted-foreground">
            Saving...
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="router.visit(pagesIndex().url)">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Back
          </Button>
          <Button variant="outline" as-child v-if="page.status === 'published'">
            <a :href="`/docs/${fullPath}`" target="_blank">
              <Eye class="mr-2 h-4 w-4" />
              View
            </a>
          </Button>
          <Button
            v-if="page.status !== 'published'"
            variant="default"
            @click="quickPublish"
            :disabled="isPublishing"
          >
            <Globe class="mr-2 h-4 w-4" />
            {{ isPublishing ? 'Publishing...' : 'Publish' }}
          </Button>
          <Button v-else variant="outline" @click="quickUnpublish" :disabled="isUnpublishing">
            <EyeOff class="mr-2 h-4 w-4" />
            {{ isUnpublishing ? 'Unpublishing...' : 'Unpublish' }}
          </Button>
        </div>
      </div>

      <div
        v-if="hasDraft"
        class="mb-4 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20"
      >
        <p class="text-sm text-amber-800 dark:text-amber-200">
          You have unsaved changes from a previous session.
        </p>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" @click="discardDraft">Discard</Button>
          <Button size="sm" @click="restoreDraft">Restore</Button>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-3">
          <div class="space-y-6 lg:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle>Content</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label for="title">Title</Label>
                  <Input
                    id="title"
                    v-model="form.title"
                    placeholder="Page title"
                    :disabled="form.processing"
                  />
                  <InputError :message="form.errors.title" />
                </div>

                <div class="space-y-2">
                  <Label for="slug">Slug</Label>
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">/docs/{{ parentPath }}</span>
                    <Input
                      id="slug"
                      v-model="form.slug"
                      placeholder="page-slug"
                      :disabled="form.processing"
                      class="flex-1"
                    />
                  </div>
                  <p class="text-xs text-muted-foreground">Full URL: /docs/{{ fullPath }}</p>
                  <InputError :message="form.errors.slug" />
                </div>

                <div v-if="showContentEditor" class="space-y-2">
                  <Label>Content</Label>
                  <PageEditor v-model="form.content" />
                  <InputError :message="form.errors.content" />
                </div>
              </CardContent>
            </Card>
          </div>

          <div class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Type & Organization</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label>Page Type</Label>
                  <Select :model-value="form.type" @update:model-value="onTypeChange">
                    <SelectTrigger>
                      <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="pt in pageTypes" :key="pt.value" :value="pt.value">
                        <div class="flex items-center gap-2">
                          <component :is="pt.icon" class="h-4 w-4" />
                          {{ pt.label }}
                        </div>
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p class="text-xs text-muted-foreground">
                    {{ pageTypes.find((pt) => pt.value === form.type)?.description }}
                  </p>
                  <InputError :message="form.errors.type" />
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

                <div class="space-y-2">
                  <Label>Status</Label>
                  <Select :model-value="form.status" @update:model-value="onStatusChange">
                    <SelectTrigger>
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="draft">Draft</SelectItem>
                      <SelectItem value="published">Published</SelectItem>
                      <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.status" />
                </div>

                <div class="space-y-2">
                  <Label>Icon (optional)</Label>
                  <IconPicker v-model="form.icon" :disabled="form.processing" />
                  <p class="text-xs text-muted-foreground">Lucide icon for navigation</p>
                  <InputError :message="form.errors.icon" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>SEO</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label for="seo_title">SEO Title</Label>
                  <Input
                    id="seo_title"
                    v-model="form.seo_title"
                    placeholder="SEO title (optional)"
                    :disabled="form.processing"
                  />
                  <InputError :message="form.errors.seo_title" />
                </div>

                <div class="space-y-2">
                  <Label for="seo_description">SEO Description</Label>
                  <Textarea
                    id="seo_description"
                    v-model="form.seo_description"
                    placeholder="SEO description (optional)"
                    :disabled="form.processing"
                    rows="3"
                  />
                  <InputError :message="form.errors.seo_description" />
                </div>
              </CardContent>
            </Card>

            <Card v-if="page.versions && page.versions.length > 0">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <History class="h-4 w-4" />
                  Version History
                </CardTitle>
              </CardHeader>
              <CardContent>
                <ul class="space-y-2">
                  <li
                    v-for="version in page.versions.slice(0, 5)"
                    :key="version.id"
                    class="flex items-center justify-between text-sm"
                  >
                    <div class="flex items-center gap-2">
                      <span class="font-medium">v{{ version.version_number }}</span>
                      <span class="text-muted-foreground">
                        {{ formatDate(version.created_at) }}
                      </span>
                    </div>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-7"
                      @click="restoreVersion(version.id)"
                      :disabled="restoringVersionId === version.id"
                    >
                      <RotateCcw class="mr-1 h-3 w-3" />
                      {{ restoringVersionId === version.id ? 'Restoring...' : 'Restore' }}
                    </Button>
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Button type="submit" :disabled="form.processing || !canSubmit" class="w-full">
              <Save class="mr-2 h-4 w-4" />
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </Button>
          </div>
        </div>
      </form>
    </div>

    <div
      class="fixed right-0 bottom-0 left-0 z-50 border-t bg-background/95 px-4 py-3 backdrop-blur supports-backdrop-filter:bg-background/60 md:left-(--sidebar-width)"
    >
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <span v-if="autoSaveStatus === 'saving'" class="flex items-center gap-1">
            <span class="h-2 w-2 animate-pulse rounded-full bg-amber-500" />
            Saving...
          </span>
          <span
            v-else-if="autoSaveStatus === 'saved'"
            class="flex items-center gap-1 text-green-600"
          >
            <Check class="h-4 w-4" />
            Saved
          </span>
          <span v-else-if="form.isDirty" class="flex items-center gap-1">
            <span class="h-2 w-2 rounded-full bg-amber-500" />
            Unsaved changes
          </span>
        </div>

        <div class="flex items-center gap-2">
          <Button
            v-if="page.status !== 'published'"
            variant="outline"
            size="sm"
            @click="quickPublish"
            :disabled="isPublishing"
          >
            <Globe class="mr-2 h-4 w-4" />
            {{ isPublishing ? 'Publishing...' : 'Publish' }}
          </Button>
          <Button
            v-else
            variant="outline"
            size="sm"
            @click="quickUnpublish"
            :disabled="isUnpublishing"
          >
            <EyeOff class="mr-2 h-4 w-4" />
            Unpublish
          </Button>
          <Button size="sm" :disabled="form.processing || !canSubmit" @click="submit">
            <Save class="mr-2 h-4 w-4" />
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
            <kbd
              class="ml-2 hidden rounded bg-primary-foreground/20 px-1.5 py-0.5 text-xs sm:inline-block"
            >
              Ctrl+S
            </kbd>
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
