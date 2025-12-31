<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { SettingsNav } from '@/components/settings';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { branding, index as settingsIndex } from '@/routes/admin/settings';
import {
  deleteLogo as deleteLogoRoute,
  update as updateBranding,
} from '@/routes/admin/settings/branding';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
  settings: Record<string, string>;
  defaults: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Site Settings', href: settingsIndex().url },
  { title: 'Branding', href: branding().url },
];

const form = useForm({
  site_name: props.settings.branding_site_name ?? props.defaults.site_name,
  site_tagline: props.settings.branding_site_tagline ?? props.defaults.site_tagline,
  logo_light: null as File | null,
  logo_dark: null as File | null,
  favicon: null as File | null,
  social_twitter: props.settings.branding_social_twitter ?? props.defaults.social_twitter,
  social_github: props.settings.branding_social_github ?? props.defaults.social_github,
  social_discord: props.settings.branding_social_discord ?? props.defaults.social_discord,
  social_linkedin: props.settings.branding_social_linkedin ?? props.defaults.social_linkedin,
});

const logoLightPreview = ref<string | null>(
  props.settings.branding_logo_light ? `/storage/${props.settings.branding_logo_light}` : null,
);
const logoDarkPreview = ref<string | null>(
  props.settings.branding_logo_dark ? `/storage/${props.settings.branding_logo_dark}` : null,
);
const faviconPreview = ref<string | null>(
  props.settings.branding_favicon ? `/storage/${props.settings.branding_favicon}` : null,
);

const submit = () => {
  form.post(updateBranding().url, {
    forceFormData: true,
  });
};

const handleLogoLightChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    form.logo_light = file;
    logoLightPreview.value = URL.createObjectURL(file);
  }
};

const handleLogoDarkChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    form.logo_dark = file;
    logoDarkPreview.value = URL.createObjectURL(file);
  }
};

const handleFaviconChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    form.favicon = file;
    faviconPreview.value = URL.createObjectURL(file);
  }
};

const deleteLogo = (type: 'light' | 'dark') => {
  router.delete(deleteLogoRoute().url, {
    data: { type },
    preserveScroll: true,
    onSuccess: () => {
      if (type === 'light') {
        logoLightPreview.value = null;
      } else {
        logoDarkPreview.value = null;
      }
    },
  });
};
</script>

<template>
  <Head title="Branding Settings" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center justify-between">
        <Heading title="Branding Settings" description="Configure your site identity" />
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
                <CardTitle>Site Identity</CardTitle>
                <CardDescription>Your site name and tagline</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label for="site_name">Site Name</Label>
                  <Input id="site_name" v-model="form.site_name" placeholder="My Documentation" />
                  <InputError :message="form.errors.site_name" />
                </div>

                <div class="space-y-2">
                  <Label for="site_tagline">Tagline</Label>
                  <Input
                    id="site_tagline"
                    v-model="form.site_tagline"
                    placeholder="Building great things together"
                  />
                  <InputError :message="form.errors.site_tagline" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Logos</CardTitle>
                <CardDescription>Upload logos for light and dark themes</CardDescription>
              </CardHeader>
              <CardContent class="space-y-6">
                <div class="space-y-3">
                  <Label>Logo (Light Theme)</Label>
                  <div class="flex items-center gap-4">
                    <div
                      class="flex h-16 w-32 items-center justify-center rounded-lg border bg-white"
                    >
                      <img
                        v-if="logoLightPreview"
                        :src="logoLightPreview"
                        alt="Light logo"
                        class="max-h-12 max-w-28 object-contain"
                      />
                      <span v-else class="text-xs text-muted-foreground">No logo</span>
                    </div>
                    <div class="flex gap-2">
                      <Button type="button" variant="outline" size="sm" as="label">
                        <Upload class="mr-2 h-4 w-4" />
                        Upload
                        <input
                          type="file"
                          accept="image/*"
                          class="hidden"
                          @change="handleLogoLightChange"
                        />
                      </Button>
                      <Button
                        v-if="logoLightPreview"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="deleteLogo('light')"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  <InputError :message="form.errors.logo_light" />
                </div>

                <div class="space-y-3">
                  <Label>Logo (Dark Theme)</Label>
                  <div class="flex items-center gap-4">
                    <div
                      class="flex h-16 w-32 items-center justify-center rounded-lg border bg-slate-900"
                    >
                      <img
                        v-if="logoDarkPreview"
                        :src="logoDarkPreview"
                        alt="Dark logo"
                        class="max-h-12 max-w-28 object-contain"
                      />
                      <span v-else class="text-xs text-slate-400">No logo</span>
                    </div>
                    <div class="flex gap-2">
                      <Button type="button" variant="outline" size="sm" as="label">
                        <Upload class="mr-2 h-4 w-4" />
                        Upload
                        <input
                          type="file"
                          accept="image/*"
                          class="hidden"
                          @change="handleLogoDarkChange"
                        />
                      </Button>
                      <Button
                        v-if="logoDarkPreview"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="deleteLogo('dark')"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  <InputError :message="form.errors.logo_dark" />
                </div>

                <div class="space-y-3">
                  <Label>Favicon</Label>
                  <div class="flex items-center gap-4">
                    <div
                      class="flex h-12 w-12 items-center justify-center rounded-lg border bg-muted"
                    >
                      <img
                        v-if="faviconPreview"
                        :src="faviconPreview"
                        alt="Favicon"
                        class="h-8 w-8 object-contain"
                      />
                      <span v-else class="text-xs text-muted-foreground">—</span>
                    </div>
                    <Button type="button" variant="outline" size="sm" as="label">
                      <Upload class="mr-2 h-4 w-4" />
                      Upload
                      <input
                        type="file"
                        accept=".ico,.png,.svg"
                        class="hidden"
                        @change="handleFaviconChange"
                      />
                    </Button>
                  </div>
                  <p class="text-xs text-muted-foreground">Recommended: 32x32 or 64x64 pixels</p>
                  <InputError :message="form.errors.favicon" />
                </div>
              </CardContent>
            </Card>
          </div>

          <div class="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Social Links</CardTitle>
                <CardDescription>Link to your social profiles</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-2">
                  <Label for="social_twitter">Twitter / X</Label>
                  <Input
                    id="social_twitter"
                    v-model="form.social_twitter"
                    placeholder="https://twitter.com/yourhandle"
                    type="url"
                  />
                  <InputError :message="form.errors.social_twitter" />
                </div>

                <div class="space-y-2">
                  <Label for="social_github">GitHub</Label>
                  <Input
                    id="social_github"
                    v-model="form.social_github"
                    placeholder="https://github.com/yourorg"
                    type="url"
                  />
                  <InputError :message="form.errors.social_github" />
                </div>

                <div class="space-y-2">
                  <Label for="social_discord">Discord</Label>
                  <Input
                    id="social_discord"
                    v-model="form.social_discord"
                    placeholder="https://discord.gg/invite"
                    type="url"
                  />
                  <InputError :message="form.errors.social_discord" />
                </div>

                <div class="space-y-2">
                  <Label for="social_linkedin">LinkedIn</Label>
                  <Input
                    id="social_linkedin"
                    v-model="form.social_linkedin"
                    placeholder="https://linkedin.com/company/yourcompany"
                    type="url"
                  />
                  <InputError :message="form.errors.social_linkedin" />
                </div>
              </CardContent>
            </Card>

            <Button type="submit" :disabled="form.processing" class="w-full">
              <Save class="mr-2 h-4 w-4" />
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </Button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
