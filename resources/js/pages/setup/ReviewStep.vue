<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type {
  AdminFormData,
  ContentMode,
  GitConfigData,
  SiteSettingsData,
} from '@/pages/setup/types';
import { store } from '@/routes/setup';
import { Form } from '@inertiajs/vue3';
import {
  ArrowLeft,
  Building2,
  Check,
  Clock,
  Edit3,
  GitBranch,
  Globe,
  Key,
  Loader2,
  Mail,
  PanelBottom,
  Rocket,
  Type,
  User,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  contentMode: ContentMode | null;
  admin: AdminFormData;
  git: GitConfigData;
  siteSettings: SiteSettingsData;
  hasUsers: boolean;
}>();

defineEmits<{
  back: [];
}>();

const formData = computed(() => ({
  content_mode: props.contentMode,
  git_repository_url: props.contentMode === 'git' ? props.git.repositoryUrl : null,
  git_branch: props.contentMode === 'git' ? props.git.branch : null,
  git_access_token: props.contentMode === 'git' ? props.git.accessToken : null,
  git_webhook_secret: props.contentMode === 'git' ? props.git.webhookSecret : null,
  git_sync_frequency: props.contentMode === 'git' ? props.git.syncFrequency : null,
  admin_name: !props.hasUsers ? props.admin.name : undefined,
  admin_email: !props.hasUsers ? props.admin.email : undefined,
  admin_password: !props.hasUsers ? props.admin.password : undefined,
  admin_password_confirmation: !props.hasUsers ? props.admin.passwordConfirmation : undefined,
  site_name: props.siteSettings.siteName,
  site_tagline: props.siteSettings.siteTagline,
  show_footer: props.siteSettings.showFooter,
  footer_text: props.siteSettings.footerText,
  meta_robots: props.siteSettings.metaRobots,
}));

const canSubmit = computed(() => {
  if (!props.contentMode) {
    return false;
  }

  if (!props.hasUsers) {
    const { name, email, password, passwordConfirmation } = props.admin;
    if (!name || !email || !password || !passwordConfirmation) {
      return false;
    }
    if (password !== passwordConfirmation) {
      return false;
    }
  }

  if (props.contentMode === 'git') {
    if (!props.git.repositoryUrl || !props.git.branch) {
      return false;
    }
  }

  if (!props.siteSettings.siteName.trim()) {
    return false;
  }

  return true;
});
</script>

<template>
  <div class="w-full max-w-2xl space-y-6">
    <div class="text-center">
      <div
        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-500/10"
      >
        <Rocket class="h-7 w-7 text-green-500" />
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-foreground">Finally, review & complete.</h1>
      <p class="mt-2 text-muted-foreground">
        Review your settings one last time before launching your documentation, to ensure everything
        is configured correctly
      </p>
    </div>

    <Form v-bind="store.form()" :data="formData" v-slot="{ errors, processing }">
      <!-- Hidden fields for form submission -->
      <input type="hidden" name="content_mode" :value="contentMode" />
      <template v-if="contentMode === 'git'">
        <input type="hidden" name="git_repository_url" :value="git.repositoryUrl" />
        <input type="hidden" name="git_branch" :value="git.branch" />
        <input type="hidden" name="git_access_token" :value="git.accessToken" />
        <input type="hidden" name="git_webhook_secret" :value="git.webhookSecret" />
        <input type="hidden" name="git_sync_frequency" :value="git.syncFrequency" />
      </template>
      <template v-if="!hasUsers">
        <input type="hidden" name="admin_name" :value="admin.name" />
        <input type="hidden" name="admin_email" :value="admin.email" />
        <input type="hidden" name="admin_password" :value="admin.password" />
        <input
          type="hidden"
          name="admin_password_confirmation"
          :value="admin.passwordConfirmation"
        />
      </template>
      <input type="hidden" name="site_name" :value="siteSettings.siteName" />
      <input type="hidden" name="site_tagline" :value="siteSettings.siteTagline" />
      <input type="hidden" name="show_footer" :value="siteSettings.showFooter" />
      <input type="hidden" name="footer_text" :value="siteSettings.footerText" />
      <input type="hidden" name="meta_robots" :value="siteSettings.metaRobots" />

      <Card class="overflow-hidden rounded-xl border-0 shadow-lg">
        <CardContent class="p-0">
          <!-- Admin Summary (if creating user) -->
          <div v-if="!hasUsers" class="p-6">
            <div class="mb-4 flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                <User class="h-4 w-4 text-primary" />
              </div>
              <h3 class="font-semibold">Admin account</h3>
              <Badge variant="secondary" class="ml-auto">New admin</Badge>
            </div>
            <div class="space-y-3 rounded-lg bg-muted/50 p-4">
              <div class="flex items-center gap-3">
                <User class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Your name</span>
                <span class="ml-auto text-sm font-medium">{{ admin.name }}</span>
              </div>
              <div class="flex items-center gap-3">
                <Mail class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Email</span>
                <span class="ml-auto text-sm font-medium">{{ admin.email }}</span>
              </div>
            </div>
            <InputError :message="errors.admin_name" class="mt-2" />
            <InputError :message="errors.admin_email" class="mt-2" />
            <InputError :message="errors.admin_password" class="mt-2" />
          </div>

          <Separator v-if="!hasUsers" />

          <!-- Mode Summary -->
          <div class="p-6">
            <div class="mb-4 flex items-center gap-2">
              <div
                class="flex h-8 w-8 items-center justify-center rounded-lg"
                :class="contentMode === 'git' ? 'bg-blue-500/10' : 'bg-green-500/10'"
              >
                <GitBranch v-if="contentMode === 'git'" class="h-4 w-4 text-blue-500" />
                <Edit3 v-else class="h-4 w-4 text-green-500" />
              </div>
              <h3 class="font-semibold">Content mode</h3>
              <Badge
                :class="
                  contentMode === 'git'
                    ? 'bg-blue-500/10 text-blue-600 hover:bg-blue-500/20'
                    : 'bg-green-500/10 text-green-600 hover:bg-green-500/20'
                "
                class="ml-auto"
              >
                {{ contentMode === 'git' ? 'Git Mode' : 'CMS Mode' }}
              </Badge>
            </div>
            <p class="text-sm text-muted-foreground">
              {{
                contentMode === 'git'
                  ? 'You`ve chosen to sync your documentation content from a GitHub repository'
                  : 'You`ll manage your documentation content using the built-in visual editor'
              }}
            </p>
            <InputError :message="errors.content_mode" class="mt-2" />
          </div>

          <!-- Git Config Summary -->
          <template v-if="contentMode === 'git'">
            <Separator />
            <div class="p-6">
              <div class="mb-4 flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted">
                  <GitBranch class="h-4 w-4 text-muted-foreground" />
                </div>
                <h3 class="font-semibold">Repository settings</h3>
              </div>
              <div class="space-y-3 rounded-lg bg-muted/50 p-4">
                <div class="flex items-start gap-3">
                  <GitBranch class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                  <div class="min-w-0 flex-1">
                    <span class="text-sm text-muted-foreground">Connected repo: </span>
                    <p class="truncate text-sm font-medium">{{ git.repositoryUrl }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <Check class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">Branch name: </span>
                  <Badge variant="outline" class="ml-auto font-mono text-xs">
                    {{ git.branch }}
                  </Badge>
                </div>
                <div class="flex items-center gap-3">
                  <Clock class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">Sync frequency: </span>
                  <span class="ml-auto text-sm font-medium">every {{ git.syncFrequency }} min</span>
                </div>
                <div class="flex items-center gap-3">
                  <Key class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">Access token:</span>
                  <Badge
                    :variant="git.accessToken ? 'default' : 'secondary'"
                    class="ml-auto text-xs"
                  >
                    {{ git.accessToken ? 'Configured' : 'Not set' }}
                  </Badge>
                </div>
              </div>
              <InputError :message="errors.git_repository_url" class="mt-2" />
              <InputError :message="errors.git_branch" class="mt-2" />
            </div>
          </template>

          <Separator />

          <!-- Site Settings Summary -->
          <div class="p-6">
            <div class="mb-4 flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-500/10">
                <Building2 class="h-4 w-4 text-purple-500" />
              </div>
              <h3 class="font-semibold">Documentation site settings</h3>
            </div>
            <div class="space-y-3 rounded-lg bg-muted/50 p-4">
              <div class="flex items-center gap-3">
                <Type class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Your site name: </span>
                <span class="ml-auto text-sm font-medium">{{ siteSettings.siteName }}</span>
              </div>
              <div v-if="siteSettings.siteTagline" class="flex items-center gap-3">
                <Type class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Tagline: </span>
                <span class="ml-auto text-sm font-medium">{{ siteSettings.siteTagline }}</span>
              </div>
              <div v-else class="flex items-center gap-3">
                <Type class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Tagline content: </span>
                <Badge variant="secondary" class="ml-auto text-xs">Not set</Badge>
              </div>
              <div class="flex items-center gap-3">
                <PanelBottom class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Footer content: </span>
                <Badge
                  :variant="siteSettings.showFooter ? 'default' : 'secondary'"
                  class="ml-auto text-xs"
                >
                  {{ siteSettings.showFooter ? 'Enabled' : 'Disabled' }}
                </Badge>
              </div>
              <div class="flex items-center gap-3">
                <Globe class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Search engines indexing:</span>
                <Badge
                  :class="
                    siteSettings.metaRobots === 'index'
                      ? 'bg-green-500/10 text-green-600'
                      : 'bg-orange-500/10 text-orange-600'
                  "
                  class="ml-auto text-xs"
                >
                  {{ siteSettings.metaRobots === 'index' ? 'Indexed' : 'Not Indexed' }}
                </Badge>
              </div>
            </div>
            <InputError :message="errors.site_name" class="mt-2" />
          </div>
        </CardContent>
      </Card>

      <div class="flex items-center justify-between pt-4">
        <Button variant="ghost" size="sm" class="text-muted-foreground" @click="$emit('back')">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Back
        </Button>
        <Button
          type="submit"
          size="lg"
          class="h-12 gap-2 px-8 text-base font-semibold"
          :disabled="processing || !canSubmit"
        >
          <Loader2 v-if="processing" class="h-5 w-5 animate-spin" />
          <Rocket v-else class="h-5 w-5" />
          {{ processing ? 'Setting up...' : 'Complete Setup' }}
        </Button>
      </div>
    </Form>
  </div>
</template>
