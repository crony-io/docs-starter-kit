<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { SettingsNav, SliderField } from '@/components/settings';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as settingsIndex, typography } from '@/routes/admin/settings';
import { update as updateTypography } from '@/routes/admin/settings/typography';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, RotateCcw, Save } from 'lucide-vue-next';
import { computed } from 'vue';

interface FontOption {
  value: string;
  label: string;
}

interface Props {
  settings: Record<string, unknown>;
  defaults: Record<string, unknown>;
  googleFonts: FontOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Site Settings', href: settingsIndex().url },
  { title: 'Typography', href: typography().url },
];

const form = useForm({
  heading_font: String(props.settings.typography_heading_font ?? props.defaults.heading_font),
  body_font: String(props.settings.typography_body_font ?? props.defaults.body_font),
  code_font: String(props.settings.typography_code_font ?? props.defaults.code_font),
  base_font_size: Number(props.settings.typography_base_font_size ?? props.defaults.base_font_size),
  heading_scale: Number(props.settings.typography_heading_scale ?? props.defaults.heading_scale),
  line_height: Number(props.settings.typography_line_height ?? props.defaults.line_height),
  paragraph_spacing: Number(
    props.settings.typography_paragraph_spacing ?? props.defaults.paragraph_spacing,
  ),
});

const submit = () => {
  form.submit(updateTypography());
};

const resetToDefaults = () => {
  form.heading_font = String(props.defaults.heading_font);
  form.body_font = String(props.defaults.body_font);
  form.code_font = String(props.defaults.code_font);
  form.base_font_size = Number(props.defaults.base_font_size);
  form.heading_scale = Number(props.defaults.heading_scale);
  form.line_height = Number(props.defaults.line_height);
  form.paragraph_spacing = Number(props.defaults.paragraph_spacing);
};

const codeFonts = props.googleFonts.filter((f) =>
  ['JetBrains Mono', 'Fira Code', 'Source Code Pro'].includes(f.value),
);

const textFonts = props.googleFonts.filter(
  (f) => !['JetBrains Mono', 'Fira Code', 'Source Code Pro'].includes(f.value),
);

const previewStyles = computed(() => ({
  '--typo-body-font': form.body_font,
  '--typo-heading-font': form.heading_font,
  '--typo-code-font': form.code_font,
  '--typo-font-size': `${form.base_font_size}px`,
  '--typo-heading-size': `${form.base_font_size * form.heading_scale}px`,
  '--typo-line-height': form.line_height,
  '--typo-paragraph-spacing': `${form.paragraph_spacing}em`,
}));
</script>

<template>
  <Head title="Typography Settings" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <Heading title="Typography Settings" description="Configure fonts and text styling" />
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
                <CardTitle>Font Families</CardTitle>
                <CardDescription>Choose fonts for your documentation</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label>Heading Font</Label>
                  <Select v-model="form.heading_font">
                    <SelectTrigger>
                      <SelectValue placeholder="Select font" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="font in textFonts"
                        :key="font.value"
                        :value="font.value"
                        v-csp-style="{ '--font-preview': font.value }"
                        class="font-preview-item"
                      >
                        {{ font.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.heading_font" />
                </div>

                <div class="space-y-2">
                  <Label>Body Font</Label>
                  <Select v-model="form.body_font">
                    <SelectTrigger>
                      <SelectValue placeholder="Select font" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="font in textFonts"
                        :key="font.value"
                        :value="font.value"
                        v-csp-style="{ '--font-preview': font.value }"
                        class="font-preview-item"
                      >
                        {{ font.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.body_font" />
                </div>

                <div class="space-y-2">
                  <Label>Code Font</Label>
                  <Select v-model="form.code_font">
                    <SelectTrigger>
                      <SelectValue placeholder="Select font" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="font in codeFonts"
                        :key="font.value"
                        :value="font.value"
                        v-csp-style="{ '--font-preview': font.value }"
                        class="font-preview-item"
                      >
                        {{ font.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.code_font" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Size & Spacing</CardTitle>
                <CardDescription>Adjust text dimensions</CardDescription>
              </CardHeader>
              <CardContent class="space-y-6">
                <SliderField
                  v-model="form.base_font_size"
                  label="Base Font Size"
                  :min="12"
                  :max="24"
                  :step="1"
                  unit="px"
                  :error="form.errors.base_font_size"
                />
                <SliderField
                  v-model="form.heading_scale"
                  label="Heading Scale"
                  :min="1.1"
                  :max="1.5"
                  :step="0.05"
                  unit="x"
                  :description="`H1 will be ${Math.round(form.base_font_size * Math.pow(form.heading_scale, 4))}px`"
                  :error="form.errors.heading_scale"
                />
                <SliderField
                  v-model="form.line_height"
                  label="Line Height"
                  :min="1.2"
                  :max="2.0"
                  :step="0.1"
                  :error="form.errors.line_height"
                />
                <SliderField
                  v-model="form.paragraph_spacing"
                  label="Paragraph Spacing"
                  :min="0.5"
                  :max="3.0"
                  :step="0.25"
                  unit="em"
                  :error="form.errors.paragraph_spacing"
                />
              </CardContent>
            </Card>
          </div>

          <div class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Preview</CardTitle>
                <CardDescription>See how your typography looks</CardDescription>
              </CardHeader>
              <CardContent>
                <div class="typo-preview rounded-lg border p-6" v-csp-style="previewStyles">
                  <h1 class="typo-heading mb-4 font-bold">Sample Heading</h1>
                  <p class="typo-paragraph">
                    This is a sample paragraph demonstrating how your documentation text will appear
                    with the selected typography settings. Good typography improves readability.
                  </p>
                  <p class="typo-paragraph">
                    Another paragraph to show spacing between blocks of text. The line height and
                    paragraph spacing work together to create comfortable reading.
                  </p>
                  <pre class="typo-code rounded bg-muted p-3 text-sm"><code>// Code sample
function example() {
  return "Hello, World!";
}</code></pre>
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

<style scoped>
.typo-preview {
  font-family: var(--typo-body-font), system-ui, sans-serif;
  font-size: var(--typo-font-size);
  line-height: var(--typo-line-height);
}

.typo-heading {
  font-family: var(--typo-heading-font), system-ui, sans-serif;
  font-size: var(--typo-heading-size);
}

.typo-paragraph {
  margin-bottom: var(--typo-paragraph-spacing);
}

.typo-code {
  font-family: var(--typo-code-font), monospace;
}

.font-preview-item {
  font-family: var(--font-preview), system-ui, sans-serif;
}
</style>
