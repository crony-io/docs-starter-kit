<script setup lang="ts">
import DynamicFormField from '@/components/docs/DynamicFormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { store as feedbackStore } from '@/routes/feedback';
import type { FeedbackForm, FeedbackFormField } from '@/types/feedback';
import { useForm } from '@inertiajs/vue3';
import { ThumbsDown, ThumbsUp } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Props {
  pageId: number;
  forms: FeedbackForm[];
}

const props = defineProps<Props>();

const feedbackState = ref<'idle' | 'form' | 'submitted'>('idle');
const isHelpful = ref<boolean | null>(null);
const formData = reactive<Record<string, string | number | string[]>>({});
const defaultComment = ref('');

interface FeedbackFormData {
  page_id: number;
  feedback_form_id: number | null;
  is_helpful: boolean | null;
  form_data: Record<string, string | number | string[]> | null;
}

const form = useForm<FeedbackFormData>({
  page_id: props.pageId,
  feedback_form_id: null,
  is_helpful: null,
  form_data: null,
});

const activeForm = computed<FeedbackForm | null>(() => {
  if (isHelpful.value === null) {
    return null;
  }

  const triggerType = isHelpful.value ? 'positive' : 'negative';

  return (
    props.forms.find((f) => f.trigger_type === triggerType) ||
    props.forms.find((f) => f.trigger_type === 'always') ||
    null
  );
});

const handleVote = (helpful: boolean) => {
  isHelpful.value = helpful;
  form.is_helpful = helpful;

  // Reset form data
  Object.keys(formData).forEach((key) => delete formData[key]);
  defaultComment.value = '';

  if (activeForm.value) {
    form.feedback_form_id = activeForm.value.id;
    activeForm.value.fields.forEach((field: FeedbackFormField) => {
      formData[field.label] = field.type === 'checkbox' ? [] : '';
    });
  }

  feedbackState.value = 'form';
};

const submitFeedback = () => {
  // If using custom form, use formData; otherwise use defaultComment
  if (activeForm.value) {
    const hasData = Object.values(formData).some((v) =>
      Array.isArray(v) ? v.length > 0 : v !== '',
    );
    form.form_data = hasData ? { ...formData } : null;
  } else {
    // No custom form - use default comment field
    form.form_data = defaultComment.value.trim() ? { comment: defaultComment.value.trim() } : null;
  }

  form.post(feedbackStore.url(), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      feedbackState.value = 'submitted';
    },
  });
};
</script>

<template>
  <div class="mt-12 border-t pt-8">
    <div v-if="feedbackState === 'idle'" class="flex flex-col items-center gap-4">
      <p class="text-sm text-muted-foreground">Was this page helpful?</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" @click="handleVote(true)">
          <ThumbsUp class="mr-2 h-4 w-4" />
          Yes
        </Button>
        <Button variant="outline" size="sm" @click="handleVote(false)">
          <ThumbsDown class="mr-2 h-4 w-4" />
          No
        </Button>
      </div>
    </div>

    <div v-else-if="feedbackState === 'form'" class="flex flex-col items-center gap-4">
      <p class="text-sm text-muted-foreground">
        {{
          isHelpful ? 'Great! Any additional comments?' : 'Sorry to hear that. How can we improve?'
        }}
      </p>
      <div class="w-full max-w-md space-y-4">
        <!-- Custom form fields -->
        <template v-if="activeForm">
          <DynamicFormField
            v-for="(field, index) in activeForm.fields"
            :key="index"
            :field="field"
            v-model="formData[field.label]"
          />
        </template>
        <!-- Default comment textarea when no form exists -->
        <template v-else>
          <Textarea
            v-model="defaultComment"
            :placeholder="
              isHelpful
                ? 'Tell us what you found helpful...'
                : 'Tell us how we can improve this page...'
            "
            rows="3"
            class="resize-none"
          />
        </template>
        <div class="flex justify-center gap-2">
          <Button variant="outline" size="sm" @click="submitFeedback" :disabled="form.processing">
            Skip
          </Button>
          <Button size="sm" @click="submitFeedback" :disabled="form.processing">
            {{ form.processing ? 'Submitting...' : 'Submit' }}
          </Button>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-col items-center gap-2">
      <p class="text-sm font-medium text-green-600">Thank you for your feedback!</p>
      <p class="text-xs text-muted-foreground">Your input helps us improve our documentation.</p>
    </div>
  </div>
</template>
