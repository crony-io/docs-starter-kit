<script setup lang="ts">
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { StatusOption } from '@/types';

interface Props {
  search: string;
  statusFilter: string;
  typeFilter: string;
  statuses: StatusOption[];
  types: StatusOption[];
}

defineProps<Props>();

const emit = defineEmits<{
  (e: 'update:search', value: string): void;
  (e: 'update:statusFilter', value: string): void;
  (e: 'update:typeFilter', value: string): void;
}>();
</script>

<template>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
    <div class="flex-1">
      <Input
        :model-value="search"
        placeholder="Search pages..."
        class="max-w-sm"
        @update:model-value="emit('update:search', String($event))"
      />
    </div>
    <Select
      :model-value="typeFilter"
      @update:model-value="emit('update:typeFilter', String($event ?? 'all'))"
    >
      <SelectTrigger class="w-[180px]">
        <SelectValue placeholder="Filter by type" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="t in types" :key="t.value" :value="t.value">
          {{ t.label }}
        </SelectItem>
      </SelectContent>
    </Select>
    <Select
      :model-value="statusFilter"
      @update:model-value="emit('update:statusFilter', String($event ?? 'all'))"
    >
      <SelectTrigger class="w-[180px]">
        <SelectValue placeholder="Filter by status" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
          {{ status.label }}
        </SelectItem>
      </SelectContent>
    </Select>
  </div>
</template>
