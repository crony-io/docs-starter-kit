<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { test } from '@/routes/admin/git-sync';
import { store } from '@/routes/admin/setup';
import { Form, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const step = ref<'mode' | 'git-config' | 'complete'>('mode');
const selectedMode = ref<'git' | 'cms' | null>(null);

const gitRepositoryUrl = ref<string>('');
const gitBranch = ref<string>('main');
const gitAccessToken = ref<string>('');

const testConnection = () => {
  router.post(
    test(),
    {
      git_repository_url: gitRepositoryUrl.value,
      git_branch: gitBranch.value,
      git_access_token: gitAccessToken.value,
    },
    {
      preserveScroll: true,
    },
  );
};

const selectMode = (mode: 'git' | 'cms') => {
  selectedMode.value = mode;

  if (mode === 'cms') {
    // CMS mode - show the form immediately
    step.value = 'complete';
  } else {
    // Git mode needs configuration
    step.value = 'git-config';
  }
};
</script>
<template>
  <AppLayout title="Setup Wizard">
    <div class="mx-auto max-w-4xl py-12">
      <!-- Welcome & Mode Selection -->
      <div v-if="step === 'mode'" class="space-y-8">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-900">Welcome to Docs Starter Kit</h1>
          <p class="mt-2 text-gray-600">Choose how you want to manage your documentation</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <!-- Git Mode -->
          <button
            @click="selectMode('git')"
            class="rounded-lg border-2 p-8 text-left transition hover:border-blue-500"
            :class="selectedMode === 'git' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
          >
            <div class="mb-4 flex items-center space-x-3">
              <svg
                class="h-8 w-8 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"
                />
              </svg>
              <h3 class="text-xl font-semibold">Git Mode</h3>
            </div>
            <p class="mb-4 text-gray-600">
              Sync documentation from your GitHub repository. Perfect for developers and teams using
              Git.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li>✓ Automatic sync from GitHub</li>
              <li>✓ Version control with Git</li>
              <li>✓ PR workflow for docs</li>
              <li>✓ Webhook support</li>
            </ul>
          </button>

          <!-- CMS Mode -->
          <button
            @click="selectMode('cms')"
            class="rounded-lg border-2 p-8 text-left transition hover:border-green-500"
            :class="selectedMode === 'cms' ? 'border-green-500 bg-green-50' : 'border-gray-200'"
          >
            <div class="mb-4 flex items-center space-x-3">
              <svg
                class="h-8 w-8 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              <h3 class="text-xl font-semibold">CMS Mode</h3>
            </div>
            <p class="mb-4 text-gray-600">
              Visual editor with full admin panel. Perfect for non-technical teams.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li>✓ WYSIWYG editor</li>
              <li>✓ Drag & drop organization</li>
              <li>✓ File manager</li>
              <li>✓ No Git knowledge needed</li>
            </ul>
          </button>
        </div>
      </div>

      <!-- CMS Mode Form -->
      <div v-if="step === 'complete' && selectedMode === 'cms'" class="space-y-8">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-900">Setup Complete!</h1>
          <p class="mt-2 text-gray-600">Your CMS mode setup is ready.</p>
        </div>
        <Form v-bind="store.form()" v-slot="{ processing }">
          <input type="hidden" name="content_mode" value="cms" />
          <button
            type="submit"
            :disabled="processing"
            class="mx-auto rounded-lg bg-green-600 px-8 py-3 text-white hover:bg-green-700 disabled:opacity-50"
          >
            Complete Setup (CMS Mode)
          </button>
        </Form>
      </div>

      <!-- Git Configuration -->
      <div v-if="step === 'git-config'" class="space-y-8">
        <div>
          <button @click="step = 'mode'" class="mb-4 text-sm text-gray-600 hover:text-gray-900">
            ← Back to mode selection
          </button>
          <h1 class="text-3xl font-bold text-gray-900">Configure Git Repository</h1>
          <p class="mt-2 text-gray-600">Connect your GitHub repository to sync documentation</p>
        </div>

        <!-- Git Configuration Form -->
        <Form v-if="step === 'git-config'" v-bind="store.form()" v-slot="{ errors, processing }">
          <div class="space-y-6">
            <!-- Repository URL -->
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700"> Repository URL * </label>
              <input
                name="git_repository_url"
                type="url"
                v-model="gitRepositoryUrl"
                placeholder="https://github.com/username/repository"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
                required
              />
              <p class="mt-1 text-sm text-gray-500">Public or private GitHub repository URL</p>
              <div v-if="errors.git_repository_url" class="text-sm text-red-600">
                {{ errors.git_repository_url }}
              </div>
            </div>

            <!-- Branch -->
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700"> Branch * </label>
              <input
                name="git_branch"
                type="text"
                v-model="gitBranch"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.git_branch" class="text-sm text-red-600">
                {{ errors.git_branch }}
              </div>
            </div>

            <!-- Access Token -->
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Access Token (for private repositories)
              </label>
              <input
                name="git_access_token"
                type="password"
                v-model="gitAccessToken"
                placeholder="ghp_xxxxxxxxxxxx"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
              <p class="mt-1 text-sm text-gray-500">
                <a
                  href="https://github.com/settings/tokens"
                  target="_blank"
                  class="text-blue-600 hover:underline"
                >
                  Generate a personal access token →
                </a>
              </p>
              <div v-if="errors.git_access_token" class="text-sm text-red-600">
                {{ errors.git_access_token }}
              </div>
            </div>

            <!-- Sync Frequency -->
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Sync Frequency (minutes)
              </label>
              <input
                name="git_sync_frequency"
                type="number"
                value="15"
                min="5"
                max="1440"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
              <p class="mt-1 text-sm text-gray-500">
                How often to check for updates (5-1440 minutes)
              </p>
              <div v-if="errors.git_sync_frequency" class="text-sm text-red-600">
                {{ errors.git_sync_frequency }}
              </div>
            </div>

            <!-- Webhook Secret -->
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Webhook Secret (optional)
              </label>
              <input
                name="git_webhook_secret"
                type="text"
                placeholder="Generate a secure random string"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
              <p class="mt-1 text-sm text-gray-500">
                For instant updates via webhook. Configure in GitHub repository settings.
              </p>
              <div v-if="errors.git_webhook_secret" class="text-sm text-red-600">
                {{ errors.git_webhook_secret }}
              </div>
            </div>

            <!-- Hidden content_mode field -->
            <input type="hidden" name="content_mode" value="git" />

            <!-- Actions -->
            <div class="flex items-center space-x-4 pt-4">
              <button
                type="button"
                @click="testConnection"
                class="rounded-lg border border-gray-300 px-6 py-2 hover:bg-gray-50 disabled:opacity-50"
                :disabled="gitRepositoryUrl.trim().length === 0 || gitBranch.trim().length === 0"
              >
                Test Connection
              </button>

              <button
                type="submit"
                :disabled="processing"
                class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
              >
                Complete Setup
              </button>
            </div>
          </div>
        </Form>
      </div>
    </div>
  </AppLayout>
</template>
