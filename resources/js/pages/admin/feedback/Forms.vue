<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { forms as feedbackForms, index as feedbackIndex } from '@/routes/admin/feedback';
import {
  create as createForm,
  destroy as destroyForm,
  edit as editForm,
} from '@/routes/admin/feedback/forms';
import type { BreadcrumbItem } from '@/types';
import type { FeedbackForm } from '@/types/feedback';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

defineProps<{ forms: FeedbackForm[] }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Feedback', href: feedbackIndex().url },
  { title: 'Forms', href: feedbackForms().url },
];

const deleteForm = (id: number) => {
  if (confirm('Delete this form? All responses using this form will be preserved.')) {
    router.delete(destroyForm(id).url);
  }
};

const triggerLabels = {
  positive: 'Positive Response',
  negative: 'Negative Response',
  always: 'Always Show',
};
</script>

<template>
  <Head title="Feedback Forms" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex items-center justify-between">
        <Heading title="Feedback Forms" description="Manage custom feedback forms" />
        <Button as-child>
          <Link :href="createForm()">
            <Plus class="mr-2 h-4 w-4" />
            Create Form
          </Link>
        </Button>
      </div>

      <div class="mt-6 rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Trigger</TableHead>
              <TableHead>Fields</TableHead>
              <TableHead>Responses</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="w-[100px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="forms.length === 0">
              <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                No feedback forms yet.
              </TableCell>
            </TableRow>
            <TableRow v-for="form in forms" :key="form.id">
              <TableCell class="font-medium">{{ form.name }}</TableCell>
              <TableCell>{{ triggerLabels[form.trigger_type] }}</TableCell>
              <TableCell>{{ form.fields.length }} fields</TableCell>
              <TableCell>{{ form.responses_count ?? 0 }}</TableCell>
              <TableCell>
                <StatusBadge :status="form.is_active ? 'published' : 'draft'" />
              </TableCell>
              <TableCell>
                <div class="flex gap-1">
                  <Button variant="ghost" size="icon" as-child>
                    <Link :href="editForm(form.id)">
                      <Pencil class="h-4 w-4" />
                    </Link>
                  </Button>
                  <Button variant="ghost" size="icon" @click="deleteForm(form.id)">
                    <Trash2 class="h-4 w-4 text-destructive" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
