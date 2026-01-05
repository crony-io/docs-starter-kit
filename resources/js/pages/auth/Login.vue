<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppearance } from '@/composables/useAppearance';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
  status?: string;
  flash?: {
    info?: string;
  };
  turnstileSiteKey?: string | null;
}>();

const nonce = ref<string | null>(null);

const { appearance } = useAppearance();

// Convert appearance to Turnstile-compatible theme
const turnstileTheme = computed<'light' | 'dark' | 'auto'>(() => {
  if (appearance.value === 'system') {
    return 'auto';
  }
  return appearance.value as 'light' | 'dark';
});

const turnstileSiteKey = props.turnstileSiteKey ?? null;
let turnstileInstance: string | null = null;
let turnstileScript: HTMLScriptElement | null = null;

const form = ref({
  turnstile_token: '',
});

const loadScript = () => {
  return new Promise((resolve, reject) => {
    turnstileScript = document.createElement('script');
    turnstileScript.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
    if (nonce.value) {
      turnstileScript.setAttribute('nonce', nonce.value);
    }
    turnstileScript.onload = resolve;
    turnstileScript.onerror = reject;
    document.body.appendChild(turnstileScript);
  });
};

const renderTurnstile = async () => {
  if (!window.turnstile) {
    await loadScript();
  }
  if (window.turnstile) {
    const container = document.querySelector('.cf-turnstile');
    if (container) {
      turnstileInstance = window.turnstile.render(container, {
        sitekey: turnstileSiteKey!,
        theme: turnstileTheme.value,
        callback: function (token: string) {
          form.value.turnstile_token = token;
        },
      });
    }
  }
};

const refreshTurnstile = async () => {
  try {
    form.value.turnstile_token = '';

    if (turnstileInstance && window.turnstile) {
      try {
        window.turnstile.reset(turnstileInstance);
      } catch {
        try {
          window.turnstile.remove(turnstileInstance);
          turnstileInstance = null;
        } catch {
          // Silently handle remove failure
        }

        await renderTurnstile();
      }
    } else {
      if (turnstileSiteKey) {
        await renderTurnstile();
      }
    }
  } catch {
    // Handle refresh failure by re-rendering

    const container = document.querySelector('.cf-turnstile');
    if (container) {
      container.innerHTML = '';
      await renderTurnstile();
    }
  }
};

const handleError = (errors: Record<string, string>) => {
  // Refresh Turnstile on any error, especially turnstile_token errors
  if (errors.turnstile_token || Object.keys(errors).length > 0) {
    setTimeout(() => {
      refreshTurnstile();
    }, 100);
  }
};

const handleFinish = () => {
  // This runs after every form submission (success or error)
  // We'll refresh on the next tick if there are validation errors
};

onMounted(() => {
  // Get CSP nonce from meta tag (client-side only)
  const nonceMeta = document.querySelector('meta[name="csp-nonce"]');
  nonce.value = nonceMeta ? nonceMeta.getAttribute('content') : null;

  if (turnstileSiteKey) {
    renderTurnstile();
  }
});

onUnmounted(() => {
  if (turnstileScript) {
    document.body.removeChild(turnstileScript);
  }
});
</script>

<template>
  <AuthBase title="Sign in to your account" description="Enter your email and password to sign in">
    <Head title="Sign in" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <div
      v-if="flash?.info"
      class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400"
    >
      {{ flash.info }}
    </div>

    <Form
      v-bind="store.form()"
      :reset-on-success="['password']"
      :on-error="handleError"
      :on-finish="handleFinish"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="email">Email</Label>
          <Input
            id="email"
            type="email"
            name="email"
            required
            autofocus
            :tabindex="1"
            autocomplete="email"
            placeholder="email@example.com"
          />
          <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
          <div class="flex items-center justify-between">
            <Label for="password">Password</Label>
          </div>
          <Input
            id="password"
            type="password"
            name="password"
            required
            :tabindex="2"
            autocomplete="current-password"
            placeholder="Password"
          />
          <InputError :message="errors.password" />
        </div>

        <div v-if="turnstileSiteKey" class="grid gap-2">
          <div
            class="cf-turnstile h-[65px]"
            :data-sitekey="turnstileSiteKey"
            :data-theme="turnstileTheme"
          />
          <input
            id="turnstile-token"
            v-model="form.turnstile_token"
            type="hidden"
            name="turnstile_token"
          />
          <InputError :message="errors.turnstile_token" />
        </div>

        <div class="flex items-center justify-between">
          <Label for="remember" class="flex items-center space-x-3">
            <Checkbox id="remember" name="remember" :tabindex="3" />
            <span>Remember me</span>
          </Label>
        </div>

        <Button
          type="submit"
          class="mt-4 w-full"
          :tabindex="4"
          :disabled="processing"
          data-test="login-button"
        >
          <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
          Sign in
        </Button>
      </div>
    </Form>
  </AuthBase>
</template>
