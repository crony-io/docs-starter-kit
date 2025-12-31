<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { SettingsNav, SwitchField } from '@/components/settings';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { advanced, index as settingsIndex } from '@/routes/admin/settings';
import { update as updateAdvanced } from '@/routes/admin/settings/advanced';
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
  { title: 'Advanced', href: advanced().url },
];

const form = useForm({
  custom_domain: String(props.settings.advanced_custom_domain ?? props.defaults.custom_domain),
  analytics_ga4_id: String(
    props.settings.advanced_analytics_ga4_id ?? props.defaults.analytics_ga4_id,
  ),
  analytics_plausible_domain: String(
    props.settings.advanced_analytics_plausible_domain ?? props.defaults.analytics_plausible_domain,
  ),
  search_enabled: Boolean(props.settings.advanced_search_enabled ?? props.defaults.search_enabled),
  search_provider: String(
    props.settings.advanced_search_provider ?? props.defaults.search_provider,
  ),
  llm_txt_enabled: Boolean(
    props.settings.advanced_llm_txt_enabled ?? props.defaults.llm_txt_enabled,
  ),
  llm_txt_include_drafts: Boolean(
    props.settings.advanced_llm_txt_include_drafts ?? props.defaults.llm_txt_include_drafts,
  ),
  llm_txt_max_tokens: Number(
    props.settings.advanced_llm_txt_max_tokens ?? props.defaults.llm_txt_max_tokens,
  ),
  meta_robots: String(props.settings.advanced_meta_robots ?? props.defaults.meta_robots),
  code_copy_button: Boolean(
    props.settings.advanced_code_copy_button ?? props.defaults.code_copy_button,
  ),
  code_line_numbers: Boolean(
    props.settings.advanced_code_line_numbers ?? props.defaults.code_line_numbers,
  ),
});

const submit = () => {
  form.submit(updateAdvanced());
};

const resetToDefaults = () => {
  form.custom_domain = String(props.defaults.custom_domain);
  form.analytics_ga4_id = String(props.defaults.analytics_ga4_id);
  form.analytics_plausible_domain = String(props.defaults.analytics_plausible_domain);
  form.search_enabled = Boolean(props.defaults.search_enabled);
  form.search_provider = String(props.defaults.search_provider);
  form.llm_txt_enabled = Boolean(props.defaults.llm_txt_enabled);
  form.llm_txt_include_drafts = Boolean(props.defaults.llm_txt_include_drafts);
  form.llm_txt_max_tokens = Number(props.defaults.llm_txt_max_tokens);
  form.meta_robots = String(props.defaults.meta_robots);
  form.code_copy_button = Boolean(props.defaults.code_copy_button);
  form.code_line_numbers = Boolean(props.defaults.code_line_numbers);
};
</script>

<template>
  <Head title="Advanced Settings" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <Heading title="Advanced Settings" description="Configure advanced features" />
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
                <CardTitle>Analytics</CardTitle>
                <CardDescription>Track your documentation usage</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label for="analytics_ga4_id">Google Analytics 4 ID</Label>
                  <Input
                    id="analytics_ga4_id"
                    v-model="form.analytics_ga4_id"
                    placeholder="G-XXXXXXXXXX"
                  />
                  <InputError :message="form.errors.analytics_ga4_id" />
                </div>

                <div class="space-y-2">
                  <Label for="analytics_plausible_domain">Plausible Domain</Label>
                  <Input
                    id="analytics_plausible_domain"
                    v-model="form.analytics_plausible_domain"
                    placeholder="yourdomain.com"
                  />
                  <p class="text-xs text-muted-foreground">
                    Privacy-friendly alternative to Google Analytics
                  </p>
                  <InputError :message="form.errors.analytics_plausible_domain" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Search</CardTitle>
                <CardDescription>Configure documentation search</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <SwitchField
                  v-model="form.search_enabled"
                  label="Enable Search"
                  description="Allow users to search documentation"
                />

                <div v-if="form.search_enabled" class="space-y-2">
                  <Label>Search Provider</Label>
                  <Select v-model="form.search_provider">
                    <SelectTrigger>
                      <SelectValue placeholder="Select provider" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="local">Local (built-in)</SelectItem>
                      <SelectItem value="meilisearch">Meilisearch</SelectItem>
                      <SelectItem value="algolia">Algolia</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.search_provider" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>LLM Integration</CardTitle>
                <CardDescription>AI-friendly documentation files</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <SwitchField
                  v-model="form.llm_txt_enabled"
                  label="Generate llms.txt"
                  description="Create AI-readable documentation"
                />

                <div v-if="form.llm_txt_enabled" class="space-y-4">
                  <SwitchField
                    v-model="form.llm_txt_include_drafts"
                    label="Include Drafts"
                    description="Include draft pages in LLM files"
                  />

                  <div class="space-y-2">
                    <Label for="llm_txt_max_tokens">Max Tokens</Label>
                    <Input
                      id="llm_txt_max_tokens"
                      v-model.number="form.llm_txt_max_tokens"
                      type="number"
                      min="1000"
                      max="1000000"
                    />
                    <p class="text-xs text-muted-foreground">
                      Maximum tokens for llms-full.txt (affects file size)
                    </p>
                    <InputError :message="form.errors.llm_txt_max_tokens" />
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <div class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Domain & SEO</CardTitle>
                <CardDescription>Configure domain and search engine settings</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label for="custom_domain">Custom Domain</Label>
                  <Input
                    id="custom_domain"
                    v-model="form.custom_domain"
                    placeholder="docs.yourdomain.com"
                  />
                  <p class="text-xs text-muted-foreground">
                    Configure DNS separately after setting this
                  </p>
                  <InputError :message="form.errors.custom_domain" />
                </div>

                <div class="space-y-2">
                  <Label>Search Engine Indexing</Label>
                  <Select v-model="form.meta_robots">
                    <SelectTrigger>
                      <SelectValue placeholder="Select option" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="index">Index (allow search engines)</SelectItem>
                      <SelectItem value="noindex">No Index (block search engines)</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.meta_robots" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Code Blocks</CardTitle>
                <CardDescription>Configure code display options</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <SwitchField
                  v-model="form.code_copy_button"
                  label="Copy Button"
                  description="Show copy button on code blocks"
                />
                <SwitchField
                  v-model="form.code_line_numbers"
                  label="Line Numbers"
                  description="Show line numbers in code blocks"
                />
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
