<script setup lang="ts">
import FormBuilder from '@/components/feedback/FormBuilder.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SwitchField from '@/components/settings/SwitchField.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { forms as feedbackForms, index as feedbackIndex } from '@/routes/admin/feedback';
import {
  edit as editFeedbackForm,
  update as updateFeedbackForm,
} from '@/routes/admin/feedback/forms';
import type { BreadcrumbItem } from '@/types';
import type { FeedbackForm } from '@/types/feedback';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';

const props = defineProps<{ form: FeedbackForm }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Feedback', href: feedbackIndex().url },
  { title: 'Forms', href: feedbackForms().url },
  { title: 'Edit', href: editFeedbackForm(props.form.id).url },
];

const formData = useForm({
  name: props.form.name,
  trigger_type: props.form.trigger_type,
  fields: props.form.fields,
  is_active: Boolean(props.form.is_active),
});

const submit = () => {
  formData.submit(updateFeedbackForm(props.form.id));
};
</script>

<template>
  <Head :title="`Edit: ${form.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <Heading :title="form.name" description="Edit feedback form" />
        <Button variant="outline" @click="router.visit(feedbackForms().url)">
          <ArrowLeft class="mr-2 h-4 w-4" /> Back
        </Button>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Form Settings</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="name">Form Name</Label>
              <Input id="name" v-model="formData.name" placeholder="e.g., Detailed Feedback" />
              <InputError :message="formData.errors.name" />
            </div>

            <div class="space-y-2">
              <Label>Show Form When</Label>
              <Select v-model="formData.trigger_type">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="always">Always (both positive & negative)</SelectItem>
                  <SelectItem value="positive">Positive response only</SelectItem>
                  <SelectItem value="negative">Negative response only</SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="formData.errors.trigger_type" />
            </div>

            <SwitchField
              v-model="formData.is_active"
              label="Active"
              description="Form will be shown to users"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Form Fields</CardTitle>
          </CardHeader>
          <CardContent>
            <FormBuilder v-model:fields="formData.fields" />
            <InputError :message="formData.errors.fields" class="mt-2" />
          </CardContent>
        </Card>

        <Button type="submit" :disabled="formData.processing" class="w-full">
          <Save class="mr-2 h-4 w-4" />
          {{ formData.processing ? 'Saving...' : 'Save Changes' }}
        </Button>
      </form>
    </div>
  </AppLayout>
</template>
