<script setup lang="ts">
import AdminAccountStep from '@/pages/setup/AdminAccountStep.vue';
import ContentModeStep from '@/pages/setup/ContentModeStep.vue';
import GitConfigStep from '@/pages/setup/GitConfigStep.vue';
import ReviewStep from '@/pages/setup/ReviewStep.vue';
import SetupLayout from '@/pages/setup/SetupLayout.vue';
import SetupProgress from '@/pages/setup/SetupProgress.vue';
import SiteSettingsStep from '@/pages/setup/SiteSettingsStep.vue';
import type {
  AdminFormData,
  ContentMode,
  GitConfigData,
  SetupStep,
  SiteSettingsData,
} from '@/pages/setup/types';
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, reactive } from 'vue';

interface StepInfo {
  id: SetupStep;
  label: string;
}

const props = defineProps<{
  hasUsers: boolean;
  isSetupCompleted: boolean;
}>();

const step = reactive<{ current: SetupStep }>({
  current: props.hasUsers ? 'mode' : 'admin',
});

const contentMode = reactive<{ value: ContentMode | null }>({
  value: null,
});

const adminForm = reactive<AdminFormData>({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
});

const gitConfig = reactive<GitConfigData>({
  repositoryUrl: '',
  branch: 'main',
  accessToken: '',
  syncFrequency: 15,
  webhookSecret: '',
});

const siteSettings = reactive<SiteSettingsData>({
  siteName: 'My Documentation',
  siteTagline: '',
  showFooter: true,
  footerText: '',
  metaRobots: 'index',
});

const allSteps: StepInfo[] = [
  { id: 'admin', label: 'Account' },
  { id: 'mode', label: 'Mode' },
  { id: 'git-config', label: 'Git Config' },
  { id: 'site-settings', label: 'Site Settings' },
  { id: 'review', label: 'Review' },
];

const visibleSteps = computed(() => {
  let steps = allSteps;
  if (props.hasUsers) {
    steps = steps.filter((s) => s.id !== 'admin');
  }
  if (contentMode.value === 'cms') {
    steps = steps.filter((s) => s.id !== 'git-config');
  }
  return steps;
});

const currentStepIndex = computed(() => visibleSteps.value.findIndex((s) => s.id === step.current));

const scrollToTop = () => {
  nextTick(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
};

const goToStep = (newStep: SetupStep) => {
  step.current = newStep;
  scrollToTop();
};

const handleModeSelect = (mode: ContentMode) => {
  contentMode.value = mode;
  if (mode === 'cms') {
    goToStep('site-settings');
  } else {
    goToStep('git-config');
  }
};

const goBack = () => {
  if (step.current === 'git-config') {
    goToStep('mode');
  } else if (step.current === 'site-settings' && contentMode.value === 'cms') {
    goToStep('mode');
  } else if (step.current === 'site-settings' && contentMode.value === 'git') {
    goToStep('git-config');
  } else if (step.current === 'review') {
    goToStep('site-settings');
  } else if (step.current === 'mode' && !props.hasUsers) {
    goToStep('admin');
  }
};
</script>

<template>
  <Head title="Setup Wizard" />

  <SetupLayout max-width="4xl">
    <SetupProgress :current-step="currentStepIndex" :steps="visibleSteps" />

    <!-- Step 1: Admin Account (only if no users) -->
    <AdminAccountStep
      v-if="step.current === 'admin' && !hasUsers"
      v-model="adminForm"
      @continue="goToStep('mode')"
    />

    <!-- Step 2: Content Mode Selection -->
    <ContentModeStep
      v-if="step.current === 'mode'"
      v-model="contentMode.value"
      :show-back-button="!hasUsers"
      @select="handleModeSelect"
      @back="goToStep('admin')"
    />

    <!-- Step 3: Git Configuration -->
    <GitConfigStep
      v-if="step.current === 'git-config'"
      v-model="gitConfig"
      @continue="goToStep('site-settings')"
      @back="goToStep('mode')"
    />

    <!-- Step 4: Site Settings -->
    <SiteSettingsStep
      v-if="step.current === 'site-settings'"
      v-model="siteSettings"
      @continue="goToStep('review')"
      @back="goBack"
    />

    <!-- Step 5: Review & Complete -->
    <ReviewStep
      v-if="step.current === 'review'"
      :content-mode="contentMode.value"
      :admin="adminForm"
      :git="gitConfig"
      :site-settings="siteSettings"
      :has-users="hasUsers"
      @back="goBack"
    />
  </SetupLayout>
</template>
