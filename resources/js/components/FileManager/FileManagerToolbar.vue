<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { MediaFilters } from '@/types/media';
import { GridIcon, ListIcon, SearchIcon, Trash2Icon, UploadIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
  filters: MediaFilters;
  selectedCount?: number;
  viewMode?: 'grid' | 'list';
}

const props = withDefaults(defineProps<Props>(), {
  selectedCount: 0,
  viewMode: 'grid',
});

const emit = defineEmits<{
  'update:filters': [filters: MediaFilters];
  'update:viewMode': [mode: 'grid' | 'list'];
  upload: [];
  deleteSelected: [];
}>();

const searchQuery = ref(props.filters.search ?? '');
const selectedType = ref(props.filters.type || 'all');

const typeOptions = [
  { value: 'all', label: 'All Types' },
  { value: 'image', label: 'Images' },
  { value: 'document', label: 'Documents' },
  { value: 'video', label: 'Videos' },
  { value: 'audio', label: 'Audio' },
];

watch(searchQuery, (value) => {
  emit('update:filters', { ...props.filters, search: value || undefined });
});

watch(selectedType, (value) => {
  emit('update:filters', {
    ...props.filters,
    type: value === 'all' ? undefined : (value as MediaFilters['type']),
  });
});

const toggleViewMode = () => {
  emit('update:viewMode', props.viewMode === 'grid' ? 'list' : 'grid');
};
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-4 border-b p-4">
    <div class="flex flex-1 items-center gap-2">
      <div class="relative max-w-xs flex-1">
        <SearchIcon
          class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input v-model="searchQuery" placeholder="Search files..." class="pl-9" />
      </div>

      <Select v-model="selectedType">
        <SelectTrigger class="w-[140px]">
          <SelectValue placeholder="All Types" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="flex items-center gap-2">
      <Button
        v-if="selectedCount > 0"
        variant="destructive"
        size="sm"
        @click="emit('deleteSelected')"
      >
        <Trash2Icon class="mr-2 h-4 w-4" />
        Delete ({{ selectedCount }})
      </Button>

      <Button variant="ghost" size="icon" @click="toggleViewMode">
        <GridIcon v-if="viewMode === 'list'" class="h-4 w-4" />
        <ListIcon v-else class="h-4 w-4" />
      </Button>

      <Button @click="emit('upload')">
        <UploadIcon class="mr-2 h-4 w-4" />
        Upload
      </Button>
    </div>
  </div>
</template>
