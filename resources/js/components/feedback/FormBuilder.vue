<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { FeedbackFormField } from '@/types/feedback';
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';

const fields = defineModel<FeedbackFormField[]>('fields', { required: true });

const containerRef = ref<HTMLElement | null>(null);
let sortableInstance: Sortable | null = null;

// WeakMap to track unique keys for Vue without polluting the data
const fieldKeys = new WeakMap<FeedbackFormField, number>();
let keyCounter = 0;

const getFieldKey = (field: FeedbackFormField): number => {
  if (!fieldKeys.has(field)) {
    fieldKeys.set(field, ++keyCounter);
  }
  return fieldKeys.get(field)!;
};

const initSortable = () => {
  if (!containerRef.value) {
    return;
  }

  if (sortableInstance) {
    sortableInstance.destroy();
  }

  sortableInstance = Sortable.create(containerRef.value, {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'opacity-50',
    onStart: () => {
      document.body.style.cursor = 'grabbing';
    },
    onEnd: (evt) => {
      document.body.style.cursor = '';

      if (evt.oldIndex === undefined || evt.newIndex === undefined) {
        return;
      }
      if (evt.oldIndex === evt.newIndex) {
        return;
      }

      const newFields = [...fields.value];
      const [movedItem] = newFields.splice(evt.oldIndex, 1);
      newFields.splice(evt.newIndex, 0, movedItem);
      fields.value = newFields;

      nextTick(() => {
        initSortable();
      });
    },
  });
};

onMounted(() => {
  nextTick(() => {
    initSortable();
  });
});

onUnmounted(() => {
  if (sortableInstance) {
    sortableInstance.destroy();
  }
});

const fieldTypes = [
  { value: 'text', label: 'Text Input' },
  { value: 'textarea', label: 'Text Area' },
  { value: 'radio', label: 'Radio Buttons' },
  { value: 'checkbox', label: 'Checkboxes' },
  { value: 'rating', label: 'Rating Scale' },
  { value: 'email', label: 'Email' },
];

const addField = () => {
  fields.value.push({
    type: 'text',
    label: '',
    required: false,
    options: [],
  });
  nextTick(() => {
    initSortable();
  });
};

const removeField = (index: number) => {
  fields.value.splice(index, 1);
  nextTick(() => {
    initSortable();
  });
};

const updateFieldType = (index: number, type: string) => {
  fields.value[index].type = type as FeedbackFormField['type'];
  if (['radio', 'checkbox'].includes(type) && !fields.value[index].options?.length) {
    fields.value[index].options = ['Option 1'];
  }
};

const addOption = (index: number) => {
  if (!fields.value[index].options) {
    fields.value[index].options = [];
  }
  fields.value[index].options!.push(`Option ${fields.value[index].options!.length + 1}`);
};

const removeOption = (fieldIndex: number, optionIndex: number) => {
  fields.value[fieldIndex].options?.splice(optionIndex, 1);
};

const needsOptions = (type: string) => ['radio', 'checkbox'].includes(type);
</script>

<template>
  <div class="space-y-4">
    <div ref="containerRef" class="space-y-3">
      <div v-for="(field, index) in fields" :key="getFieldKey(field)" class="rounded-lg border p-4">
        <div class="flex items-start gap-3">
          <div
            class="drag-handle cursor-grab text-muted-foreground transition-colors hover:text-foreground"
          >
            <GripVertical class="mt-2 h-5 w-5" />
          </div>

          <div class="flex-1 space-y-3">
            <div class="grid gap-3 sm:grid-cols-2">
              <div class="space-y-1">
                <Label>Field Label</Label>
                <Input v-model="field.label" placeholder="Enter field label" />
              </div>
              <div class="space-y-1">
                <Label>Field Type</Label>
                <Select
                  :model-value="field.type"
                  @update:model-value="updateFieldType(index, $event as string)"
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="ft in fieldTypes" :key="ft.value" :value="ft.value">
                      {{ ft.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div v-if="needsOptions(field.type)" class="space-y-2">
              <Label>Options</Label>
              <div v-for="(option, optIndex) in field.options" :key="optIndex" class="flex gap-2">
                <Input v-model="field.options![optIndex]" placeholder="Option text" />
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  @click="removeOption(index, optIndex)"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
              <Button type="button" variant="outline" size="sm" @click="addOption(index)">
                <Plus class="mr-1 h-3 w-3" /> Add Option
              </Button>
            </div>

            <div class="flex items-center gap-2">
              <Checkbox :id="`required-${index}`" v-model:checked="field.required" />
              <Label :for="`required-${index}`">Required field</Label>
            </div>
          </div>

          <Button type="button" variant="ghost" size="icon" @click="removeField(index)">
            <Trash2 class="h-4 w-4 text-destructive" />
          </Button>
        </div>
      </div>
    </div>

    <Button type="button" variant="outline" class="w-full" @click="addField">
      <Plus class="mr-2 h-4 w-4" /> Add Field
    </Button>
  </div>
</template>
