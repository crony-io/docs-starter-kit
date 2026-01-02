<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminFormData } from '@/pages/setup/types';
import { ArrowRight, Eye, EyeOff, ShieldCheck, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
  modelValue: AdminFormData;
}>();

defineEmits<{
  continue: [];
}>();

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

// Direct access to form - works because parent passes a reactive object
const form = computed(() => props.modelValue);

// Directly mutate the reactive object (works with Vue 3 reactive)
const updateField = <K extends keyof AdminFormData>(field: K, value: AdminFormData[K]) => {
  (props.modelValue as AdminFormData)[field] = value;
};

const isValid = computed(() => {
  const { name, email, password, passwordConfirmation } = form.value;
  return name && email && password && passwordConfirmation && password === passwordConfirmation;
});

const passwordsMatch = computed(() => {
  const { password, passwordConfirmation } = form.value;
  return !password || !passwordConfirmation || password === passwordConfirmation;
});

const passwordStrength = computed(() => {
  const password = form.value.password;
  if (!password) {
    return { level: 0, text: '', color: '' };
  }
  let strength = 0;
  if (password.length >= 8) {
    strength++;
  }
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
    strength++;
  }
  if (/\d/.test(password)) {
    strength++;
  }
  if (/[^a-zA-Z0-9]/.test(password)) {
    strength++;
  }

  const levels = [
    { level: 0, text: '', color: '' },
    { level: 1, text: 'Weak', color: 'bg-red-500' },
    { level: 2, text: 'Fair', color: 'bg-orange-500' },
    { level: 3, text: 'Good', color: 'bg-yellow-500' },
    { level: 4, text: 'Strong', color: 'bg-green-500' },
  ];
  return levels[strength] || levels[0];
});
</script>

<template>
  <Card class="mx-auto w-full max-w-md rounded-xl border-0 shadow-lg">
    <CardHeader class="space-y-4 px-8 pt-8 pb-2 text-center">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
        <User class="h-7 w-7 text-primary" />
      </div>
      <div>
        <CardTitle class="text-2xl font-bold">Create Admin Account</CardTitle>
        <CardDescription class="mt-2">
          Set up your administrator account to get started
        </CardDescription>
      </div>
    </CardHeader>
    <CardContent class="space-y-5 px-8 pb-8">
      <div class="space-y-2">
        <Label for="admin_name">Full Name</Label>
        <Input
          id="admin_name"
          :model-value="form.name"
          type="text"
          placeholder="John Doe"
          required
          autofocus
          class="h-11"
          @update:model-value="updateField('name', $event as string)"
        />
      </div>

      <div class="space-y-2">
        <Label for="admin_email">Email Address</Label>
        <Input
          id="admin_email"
          :model-value="form.email"
          type="email"
          placeholder="admin@example.com"
          required
          class="h-11"
          @update:model-value="updateField('email', $event as string)"
        />
      </div>

      <div class="space-y-2">
        <Label for="admin_password">Password</Label>
        <div class="relative">
          <Input
            id="admin_password"
            :model-value="form.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Enter a strong password"
            required
            class="h-11 pr-10"
            @update:model-value="updateField('password', $event as string)"
          />
          <button
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
            @click="showPassword = !showPassword"
          >
            <Eye v-if="!showPassword" class="h-4 w-4" />
            <EyeOff v-else class="h-4 w-4" />
          </button>
        </div>
        <!-- Password strength indicator -->
        <div v-if="form.password" class="space-y-1">
          <div class="flex gap-1">
            <div
              v-for="i in 4"
              :key="i"
              class="h-1 flex-1 rounded-full transition-all"
              :class="i <= passwordStrength.level ? passwordStrength.color : 'bg-muted'"
            />
          </div>
          <p
            v-if="passwordStrength.text"
            class="text-xs"
            :class="{
              'text-red-500': passwordStrength.level === 1,
              'text-orange-500': passwordStrength.level === 2,
              'text-yellow-600': passwordStrength.level === 3,
              'text-green-500': passwordStrength.level === 4,
            }"
          >
            {{ passwordStrength.text }}
          </p>
        </div>
      </div>

      <div class="space-y-2">
        <Label for="admin_password_confirmation">Confirm Password</Label>
        <div class="relative">
          <Input
            id="admin_password_confirmation"
            :model-value="form.passwordConfirmation"
            :type="showPasswordConfirm ? 'text' : 'password'"
            placeholder="Confirm your password"
            required
            class="h-11 pr-10"
            @update:model-value="updateField('passwordConfirmation', $event as string)"
          />
          <button
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
            @click="showPasswordConfirm = !showPasswordConfirm"
          >
            <Eye v-if="!showPasswordConfirm" class="h-4 w-4" />
            <EyeOff v-else class="h-4 w-4" />
          </button>
        </div>
        <p
          v-if="form.passwordConfirmation && !passwordsMatch"
          class="flex items-center gap-1 text-sm text-destructive"
        >
          <ShieldCheck class="h-3.5 w-3.5" />
          Passwords do not match
        </p>
        <p
          v-else-if="form.passwordConfirmation && passwordsMatch"
          class="flex items-center gap-1 text-sm text-green-600"
        >
          <ShieldCheck class="h-3.5 w-3.5" />
          Passwords match
        </p>
      </div>

      <Button
        class="mt-4 h-11 w-full text-base font-medium"
        :disabled="!isValid"
        @click="$emit('continue')"
      >
        Continue
        <ArrowRight class="ml-2 h-4 w-4" />
      </Button>
    </CardContent>
  </Card>
</template>
