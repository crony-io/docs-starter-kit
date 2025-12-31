<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { SettingsNav, SliderField, SwitchField } from '@/components/settings';
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
import { layout, index as settingsIndex } from '@/routes/admin/settings';
import { update as updateLayout } from '@/routes/admin/settings/layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, RotateCcw, Save } from 'lucide-vue-next';

interface Props {
  settings: Record<string, unknown>;
  defaults: Record<string, unknown>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Site Settings', href: settingsIndex().url },
  { title: 'Layout', href: layout().url },
];

const form = useForm({
  sidebar_width: Number(props.settings.layout_sidebar_width ?? props.defaults.sidebar_width),
  content_width: Number(props.settings.layout_content_width ?? props.defaults.content_width),
  navigation_style: String(
    props.settings.layout_navigation_style ?? props.defaults.navigation_style,
  ),
  show_toc: Boolean(props.settings.layout_show_toc ?? props.defaults.show_toc),
  toc_position: String(props.settings.layout_toc_position ?? props.defaults.toc_position),
  show_breadcrumbs: Boolean(
    props.settings.layout_show_breadcrumbs ?? props.defaults.show_breadcrumbs,
  ),
  show_footer: Boolean(props.settings.layout_show_footer ?? props.defaults.show_footer),
  footer_text: String(props.settings.layout_footer_text ?? props.defaults.footer_text),
});

const submit = () => {
  form.submit(updateLayout());
};

const resetToDefaults = () => {
  form.sidebar_width = Number(props.defaults.sidebar_width);
  form.content_width = Number(props.defaults.content_width);
  form.navigation_style = String(props.defaults.navigation_style);
  form.show_toc = Boolean(props.defaults.show_toc);
  form.toc_position = String(props.defaults.toc_position);
  form.show_breadcrumbs = Boolean(props.defaults.show_breadcrumbs);
  form.show_footer = Boolean(props.defaults.show_footer);
  form.footer_text = String(props.defaults.footer_text);
};
</script>

<template>
  <Head title="Layout Settings" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <Heading title="Layout Settings" description="Configure page structure and navigation" />
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
                <CardTitle>Dimensions</CardTitle>
                <CardDescription>Configure layout widths</CardDescription>
              </CardHeader>
              <CardContent class="space-y-6">
                <SliderField
                  v-model="form.sidebar_width"
                  label="Sidebar Width"
                  :min="200"
                  :max="400"
                  :step="10"
                  unit="px"
                  :error="form.errors.sidebar_width"
                />
                <SliderField
                  v-model="form.content_width"
                  label="Content Width"
                  :min="600"
                  :max="1400"
                  :step="50"
                  unit="px"
                  :error="form.errors.content_width"
                />
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Navigation</CardTitle>
                <CardDescription>Configure navigation style</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label>Navigation Style</Label>
                  <Select v-model="form.navigation_style">
                    <SelectTrigger>
                      <SelectValue placeholder="Select style" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="sidebar">Sidebar only</SelectItem>
                      <SelectItem value="topnav">Top navigation only</SelectItem>
                      <SelectItem value="both">Both sidebar and top nav</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.navigation_style" />
                </div>

                <SwitchField
                  v-model="form.show_breadcrumbs"
                  label="Show Breadcrumbs"
                  description="Display navigation path"
                />
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Table of Contents</CardTitle>
                <CardDescription>Configure TOC behavior</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <SwitchField
                  v-model="form.show_toc"
                  label="Show Table of Contents"
                  description="Display page headings navigation"
                />

                <div v-if="form.show_toc" class="space-y-2">
                  <Label>TOC Position</Label>
                  <Select v-model="form.toc_position">
                    <SelectTrigger>
                      <SelectValue placeholder="Select position" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="left">Left side</SelectItem>
                      <SelectItem value="right">Right side</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.toc_position" />
                </div>
              </CardContent>
            </Card>
          </div>

          <div class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Footer</CardTitle>
                <CardDescription>Configure footer content</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <SwitchField
                  v-model="form.show_footer"
                  label="Show Footer"
                  description="Display footer on documentation pages"
                />

                <div v-if="form.show_footer" class="space-y-2">
                  <Label>Footer Text</Label>
                  <Textarea
                    v-model="form.footer_text"
                    placeholder="© 2024 Your Company. All rights reserved."
                    rows="3"
                  />
                  <p class="text-xs text-muted-foreground">Supports basic HTML tags</p>
                  <InputError :message="form.errors.footer_text" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Preview</CardTitle>
                <CardDescription>Layout visualization</CardDescription>
              </CardHeader>
              <CardContent>
                <div class="rounded-lg border bg-muted/30 p-4">
                  <div class="flex gap-2">
                    <div
                      v-if="form.navigation_style !== 'topnav'"
                      class="min-w-[60px] rounded bg-primary/20 p-2 text-center text-xs"
                      :class="[
                        form.sidebar_width <= 220 && 'w-[15%]',
                        form.sidebar_width > 220 && form.sidebar_width <= 280 && 'w-[20%]',
                        form.sidebar_width > 280 && form.sidebar_width <= 340 && 'w-[24%]',
                        form.sidebar_width > 340 && 'w-[28%]',
                      ]"
                    >
                      Sidebar
                    </div>
                    <div class="flex-1 space-y-2">
                      <div
                        v-if="form.navigation_style !== 'sidebar'"
                        class="h-6 w-full rounded bg-primary/20"
                      />
                      <div
                        v-if="form.show_breadcrumbs"
                        class="h-4 w-3/4 rounded bg-muted-foreground/20"
                      />
                      <div class="flex gap-2">
                        <div
                          v-if="form.show_toc && form.toc_position === 'left'"
                          class="w-16 rounded bg-muted-foreground/20 p-1 text-center text-xs"
                        >
                          TOC
                        </div>
                        <div class="flex-1 space-y-1">
                          <div class="h-4 w-1/2 rounded bg-muted-foreground/30" />
                          <div class="h-3 w-full rounded bg-muted-foreground/20" />
                          <div class="h-3 w-4/5 rounded bg-muted-foreground/20" />
                          <div class="h-3 w-full rounded bg-muted-foreground/20" />
                        </div>
                        <div
                          v-if="form.show_toc && form.toc_position === 'right'"
                          class="w-16 rounded bg-muted-foreground/20 p-1 text-center text-xs"
                        >
                          TOC
                        </div>
                      </div>
                      <div
                        v-if="form.show_footer"
                        class="mt-4 h-8 w-full rounded bg-muted-foreground/10 text-center text-xs leading-8"
                      >
                        Footer
                      </div>
                    </div>
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
                Reset
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
