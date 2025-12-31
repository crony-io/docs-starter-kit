<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import IconPicker from '@/components/IconPicker.vue';
import InputError from '@/components/InputError.vue';
import PageEditor from '@/components/PageEditor/PageEditor.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { create, index as pagesIndex, store } from '@/routes/admin/pages';
import type { BreadcrumbItem, Page, PageType } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Book, FileText, FolderTree, Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
  navigationTabs: Pick<Page, 'id' | 'title' | 'slug'>[];
  potentialParents: Pick<Page, 'id' | 'title' | 'slug' | 'type' | 'parent_id'>[];
  defaultParentId: number | null;
  defaultType: PageType;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Pages', href: pagesIndex().url },
  { title: 'Create', href: create().url },
];

const form = useForm({
  title: '',
  slug: '',
  type: props.defaultType as PageType,
  icon: '',
  content: '',
  status: 'draft',
  parent_id: props.defaultParentId,
  is_default: false,
  is_expanded: true,
  seo_title: '',
  seo_description: '',
});

const pageTypes = [
  {
    value: 'navigation',
    label: 'Navigation Tab',
    icon: Book,
    description: 'Top-level tab (Documentation, Guides, etc.)',
  },
  { value: 'group', label: 'Group', icon: FolderTree, description: 'Sidebar group/section' },
  {
    value: 'document',
    label: 'Document',
    icon: FileText,
    description: 'Content page with markdown',
  },
];

const showContentEditor = computed(() => form.type === 'document');
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
    if (!form.slug || form.slug === generateSlug(form.title.slice(0, -1))) {
      form.slug = generateSlug(title);
    }
  },
);

const submit = () => {
  form.submit(store());
};

const onStatusChange = (value: unknown) => {
  form.status = String(value);
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

const canSubmit = computed(() => form.title.trim().length > 0);
</script>

<template>
  <Head title="Create Page" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <Heading title="Create Page" description="Add a new documentation page" />
        <Button variant="outline" @click="router.visit(pagesIndex().url)">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Back to Pages
        </Button>
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

            <Button type="submit" :disabled="form.processing || !canSubmit" class="w-full">
              <Save class="mr-2 h-4 w-4" />
              {{ form.processing ? 'Creating...' : 'Create Page' }}
            </Button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
