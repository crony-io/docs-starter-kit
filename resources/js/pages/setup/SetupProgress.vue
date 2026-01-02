<script setup lang="ts">
import { Check } from 'lucide-vue-next';

interface StepInfo {
  label: string;
  description?: string;
}

const props = defineProps<{
  currentStep: number;
  steps: StepInfo[];
}>();

const getStepState = (index: number): 'completed' | 'current' | 'upcoming' => {
  if (index < props.currentStep) {
    return 'completed';
  }
  if (index === props.currentStep) {
    return 'current';
  }
  return 'upcoming';
};
</script>

<template>
  <div class="w-full">
    <!-- Desktop Progress -->
    <div class="hidden sm:block">
      <div class="flex items-center justify-center">
        <template v-for="(step, index) in steps" :key="index">
          <!-- Step -->
          <div class="flex flex-col items-center">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-full border-2 text-sm font-semibold transition-all duration-300"
              :class="{
                'border-primary bg-primary text-primary-foreground shadow-lg shadow-primary/25':
                  getStepState(index) === 'current',
                'border-green-500 bg-green-500 text-white': getStepState(index) === 'completed',
                'border-muted-foreground/30 bg-background text-muted-foreground':
                  getStepState(index) === 'upcoming',
              }"
            >
              <Check v-if="getStepState(index) === 'completed'" class="h-5 w-5" />
              <span v-else>{{ index + 1 }}</span>
            </div>
            <span
              class="mt-2 text-xs font-medium transition-colors"
              :class="{
                'text-primary': getStepState(index) === 'current',
                'text-green-600 dark:text-green-400': getStepState(index) === 'completed',
                'text-muted-foreground': getStepState(index) === 'upcoming',
              }"
            >
              {{ step.label }}
            </span>
          </div>

          <!-- Connector -->
          <div
            v-if="index < steps.length - 1"
            class="mx-3 h-0.5 w-16 rounded-full transition-all duration-500"
            :class="{
              'bg-green-500': index < currentStep,
              'bg-muted-foreground/20': index >= currentStep,
            }"
          />
        </template>
      </div>
    </div>

    <!-- Mobile Progress (compact) -->
    <div class="sm:hidden">
      <div class="flex items-center justify-between rounded-lg bg-card p-3">
        <div class="flex items-center gap-3">
          <div
            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
          >
            {{ currentStep + 1 }}
          </div>
          <div>
            <p class="text-sm font-medium text-foreground">{{ steps[currentStep]?.label }}</p>
            <p class="text-xs text-muted-foreground">
              Step {{ currentStep + 1 }} of {{ steps.length }}
            </p>
          </div>
        </div>
        <div class="flex gap-1">
          <div
            v-for="(_, index) in steps"
            :key="index"
            class="h-1.5 w-6 rounded-full transition-all"
            :class="{
              'bg-green-500': index < currentStep,
              'bg-primary': index === currentStep,
              'bg-muted-foreground/20': index > currentStep,
            }"
          />
        </div>
      </div>
    </div>
  </div>
</template>
