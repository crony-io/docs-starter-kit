<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { ColorField, SettingsNav } from '@/components/settings';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { index as settingsIndex, theme } from '@/routes/admin/settings';
import { update as updateTheme } from '@/routes/admin/settings/theme';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, RotateCcw, Save } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  settings: Record<string, string>;
  defaults: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Site Settings', href: settingsIndex().url },
  { title: 'Theme', href: theme().url },
];

const form = useForm({
  primary_color: props.settings.theme_primary_color ?? props.defaults.primary_color,
  secondary_color: props.settings.theme_secondary_color ?? props.defaults.secondary_color,
  accent_color: props.settings.theme_accent_color ?? props.defaults.accent_color,
  background_color: props.settings.theme_background_color ?? props.defaults.background_color,
  text_color: props.settings.theme_text_color ?? props.defaults.text_color,
  dark_mode: props.settings.theme_dark_mode ?? props.defaults.dark_mode,
  custom_css: props.settings.theme_custom_css ?? props.defaults.custom_css,
});

const submit = () => {
  form.submit(updateTheme());
};

const resetToDefaults = () => {
  form.primary_color = props.defaults.primary_color;
  form.secondary_color = props.defaults.secondary_color;
  form.accent_color = props.defaults.accent_color;
  form.background_color = props.defaults.background_color;
  form.text_color = props.defaults.text_color;
  form.dark_mode = props.defaults.dark_mode;
  form.custom_css = props.defaults.custom_css;
};

const onDarkModeChange = (value: unknown) => {
  form.dark_mode = String(value);
};

const previewStyles = computed(() => ({
  '--preview-primary': form.primary_color,
  '--preview-secondary': form.secondary_color,
  '--preview-accent': form.accent_color,
  '--preview-bg': form.background_color,
  '--preview-text': form.text_color,
}));
</script>

<template>
  <Head title="Theme Settings" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <Heading title="Theme Settings" description="Customize colors and appearance" />
        <Button variant="outline" @click="router.visit(settingsIndex().url)">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Back
        </Button>
      </div>

      <SettingsNav />

      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
          <div class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Colors</CardTitle>
                <CardDescription>Define your brand colors</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                  <ColorField
                    v-model="form.primary_color"
                    label="Primary Color"
                    placeholder="#3B82F6"
                    :error="form.errors.primary_color"
                  />
                  <ColorField
                    v-model="form.secondary_color"
                    label="Secondary Color"
                    placeholder="#6366F1"
                    :error="form.errors.secondary_color"
                  />
                  <ColorField
                    v-model="form.accent_color"
                    label="Accent Color"
                    placeholder="#F59E0B"
                    :error="form.errors.accent_color"
                  />
                  <ColorField
                    v-model="form.background_color"
                    label="Background"
                    placeholder="#FFFFFF"
                    :error="form.errors.background_color"
                  />

                  <ColorField
                    v-model="form.text_color"
                    label="Text Color"
                    placeholder="#1F2937"
                    :error="form.errors.text_color"
                  />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Dark Mode</CardTitle>
                <CardDescription>Configure dark mode behavior</CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-2">
                  <Label>Mode</Label>
                  <Select :model-value="form.dark_mode" @update:model-value="onDarkModeChange">
                    <SelectTrigger>
                      <SelectValue placeholder="Select mode" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="light">Light only</SelectItem>
                      <SelectItem value="dark">Dark only</SelectItem>
                      <SelectItem value="system">System preference</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.dark_mode" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Custom CSS</CardTitle>
                <CardDescription>Add custom styles (advanced)</CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-2">
                  <Textarea
                    v-model="form.custom_css"
                    placeholder="/* Your custom CSS here */
.docs-content {
  /* Custom styles */
}"
                    rows="8"
                    class="font-mono text-sm"
                  />
                  <InputError :message="form.errors.custom_css" />
                  <p class="text-xs text-muted-foreground">
                    CSS will be injected into the documentation pages.
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>

          <div class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Preview</CardTitle>
                <CardDescription>See how your colors look</CardDescription>
              </CardHeader>
              <CardContent>
                <div class="theme-preview rounded-lg border p-4" v-csp-style="previewStyles">
                  <div class="mb-4 flex gap-2">
                    <div class="preview-primary h-8 w-8 rounded" title="Primary" />
                    <div class="preview-secondary h-8 w-8 rounded" title="Secondary" />
                    <div class="preview-accent h-8 w-8 rounded" title="Accent" />
                  </div>
                  <h3 class="mb-2 text-lg font-semibold">Sample Heading</h3>
                  <p class="mb-3 text-sm">
                    This is sample text content showing how your documentation will appear with the
                    selected colors.
                  </p>
                  <div class="flex gap-2">
                    <button
                      type="button"
                      class="preview-btn-primary rounded px-3 py-1.5 text-sm font-medium text-white"
                    >
                      Primary Button
                    </button>
                    <button
                      type="button"
                      class="preview-btn-outline rounded border px-3 py-1.5 text-sm font-medium"
                    >
                      Secondary
                    </button>
                  </div>
                </div>
              </CardContent>
            </Card>

            <div class="flex gap-3">
              <Button
                type="button"
                variant="outline"
                @click="resetToDefaults"
                :disabled="form.processing"
              >
                <RotateCcw class="mr-2 h-4 w-4" />
                Reset to Defaults
              </Button>
              <Button type="submit" :disabled="form.processing" class="flex-1">
                <Save class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<style scoped>
.theme-preview {
  background-color: var(--preview-bg);
  color: var(--preview-text);
}

.preview-primary {
  background-color: var(--preview-primary);
}

.preview-secondary {
  background-color: var(--preview-secondary);
}

.preview-accent {
  background-color: var(--preview-accent);
}

.preview-btn-primary {
  background-color: var(--preview-primary);
}

.preview-btn-outline {
  border-color: var(--preview-primary);
  color: var(--preview-primary);
}
</style>
