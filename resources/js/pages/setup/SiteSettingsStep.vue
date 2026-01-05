<script setup lang="ts">
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { SiteSettingsData } from '@/pages/setup/types';
import { ArrowLeft, ArrowRight, Building2, Globe, PanelBottom } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  modelValue: SiteSettingsData;
}>();

defineEmits<{
  continue: [];
  back: [];
}>();

const form = computed(() => props.modelValue);

const updateField = <FieldKey extends keyof SiteSettingsData>(
  field: FieldKey,
  value: SiteSettingsData[FieldKey],
) => {
  (props.modelValue as SiteSettingsData)[field] = value;
};

const isValid = computed(() => {
  return form.value.siteName.trim().length > 0;
});
</script>

<template>
  <div class="w-full max-w-2xl space-y-6">
    <div class="text-center">
      <div
        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10"
      >
        <Building2 class="h-7 w-7 text-primary" />
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-foreground">
        Now, configure your site settings.
      </h1>
      <p class="mt-2 text-muted-foreground">
        This means to setup the identity and display options for your documentation site. You can
        change these settings later.
      </p>
    </div>

    <div class="space-y-4">
      <Card class="rounded-xl border-0 shadow-lg">
        <CardHeader class="pb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/10">
              <Building2 class="h-5 w-5 text-blue-500" />
            </div>
            <div>
              <CardTitle class="text-lg">Docs site identity</CardTitle>
              <CardDescription
                >You can think of this as the first impression of your documentation
                site</CardDescription
              >
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label for="site_name">Docs site name <span class="text-destructive">*</span></Label>
            <Input
              id="site_name"
              :model-value="form.siteName"
              placeholder="My app's documentation"
              class="h-11"
              @update:model-value="updateField('siteName', $event as string)"
            />
          </div>

          <div class="space-y-2">
            <Label for="site_tagline">Optional tagline</Label>
            <Input
              id="site_tagline"
              :model-value="form.siteTagline"
              placeholder="e.g. Building great things together"
              class="h-11"
              @update:model-value="updateField('siteTagline', $event as string)"
            />
            <p class="text-xs text-muted-foreground">
              You may add here a short description or catchphrase to be displayed below your site
              name
            </p>
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-xl border-0 shadow-lg">
        <CardHeader class="pb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-500/10">
              <PanelBottom class="h-5 w-5 text-green-500" />
            </div>
            <div>
              <CardTitle class="text-lg">Footer</CardTitle>
              <CardDescription>You may like to have a footer configured</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex items-center justify-between">
            <div class="space-y-0.5">
              <Label>Show footer</Label>
              <p class="text-sm text-muted-foreground">
                By activating this toggle, you will display a footer on your documentation site
              </p>
            </div>
            <Switch
              :checked="form.showFooter"
              @update:checked="updateField('showFooter', $event)"
            />
          </div>

          <div v-if="form.showFooter" class="space-y-2">
            <Label for="footer_text">Footer text</Label>
            <Textarea
              id="footer_text"
              :model-value="form.footerText"
              placeholder="e.g. Your Company © 2026. All rights reserved."
              rows="2"
              @update:model-value="updateField('footerText', $event as string)"
            />
            <p class="text-xs text-muted-foreground">It also supports basic HTML tags</p>
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-xl border-0 shadow-lg">
        <CardHeader class="pb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500/10">
              <Globe class="h-5 w-5 text-orange-500" />
            </div>
            <div>
              <CardTitle class="text-lg">Search engine indexing</CardTitle>
              <CardDescription
                >You control whether search engines can index your documentation or
                not</CardDescription
              >
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <Label>Which one do you prefer?</Label>
            <Select
              :model-value="form.metaRobots"
              @update:model-value="updateField('metaRobots', $event as 'index' | 'noindex')"
            >
              <SelectTrigger class="h-11">
                <SelectValue placeholder="Select option" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="index">Index allowed (allow search engines)</SelectItem>
                <SelectItem value="noindex">No index allowed (block search engines)</SelectItem>
              </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground">
              Control whether to allow or block search engines from indexing your documentation
            </p>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="flex items-center justify-between pt-2">
      <Button variant="ghost" size="sm" class="text-muted-foreground" @click="$emit('back')">
        <ArrowLeft class="mr-2 h-4 w-4" />
        Back
      </Button>
      <Button class="h-11 px-6" :disabled="!isValid" @click="$emit('continue')">
        Continue
        <ArrowRight class="ml-2 h-4 w-4" />
      </Button>
    </div>
  </div>
</template>
