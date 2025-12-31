<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ChevronsUpDown, Search } from 'lucide-vue-next';
import { computed, ref, shallowRef, watch, type Component } from 'vue';

interface Props {
  modelValue?: string;
  disabled?: boolean;
  placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  disabled: false,
  placeholder: 'Select icon...',
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const open = ref(false);
const search = ref('');
const iconsModule = shallowRef<Record<string, Component> | null>(null);
const isLoading = ref(false);

const excludedKeys = new Set([
  'default',
  'icons',
  'createLucideIcon',
  'Icon',
  'LucideIcon',
  'createElement',
  'toKebabCase',
  'mergeClasses',
  'defaultAttributes',
]);

const loadIcons = async () => {
  if (iconsModule.value || isLoading.value) {
    return;
  }
  isLoading.value = true;
  const module = await import('lucide-vue-next');
  iconsModule.value = module as unknown as Record<string, Component>;
  isLoading.value = false;
};

watch(open, (isOpen) => {
  if (isOpen && !iconsModule.value) {
    loadIcons();
  }
});

const allIconNames = computed(() => {
  if (!iconsModule.value) {
    return [];
  }
  return Object.keys(iconsModule.value).filter(
    (name) => /^[A-Z]/.test(name) && !excludedKeys.has(name),
  );
});

const filteredIcons = computed(() => {
  const query = search.value.toLowerCase().trim();
  const names = allIconNames.value;
  if (!query) {
    return names.slice(0, 150);
  }
  return names.filter((name) => name.toLowerCase().includes(query)).slice(0, 150);
});

const getIconComponent = (name: string): Component | null => {
  if (!iconsModule.value) {
    return null;
  }
  return iconsModule.value[name] || null;
};

const selectedIconComponent = computed(() => {
  if (!props.modelValue) {
    return null;
  }
  const pascalName = kebabToPascal(props.modelValue);
  return getIconComponent(pascalName);
});

const kebabToPascal = (str: string): string => {
  return str
    .split('-')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');
};

const pascalToKebab = (str: string): string => {
  return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
};

const selectIcon = (iconName: string) => {
  const kebabName = pascalToKebab(iconName);
  emit('update:modelValue', kebabName);
  open.value = false;
  search.value = '';
};

const clearIcon = () => {
  emit('update:modelValue', '');
};
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        :disabled="disabled"
        class="w-full justify-between"
      >
        <div class="flex items-center gap-2">
          <component
            v-if="selectedIconComponent"
            :is="selectedIconComponent"
            class="h-4 w-4 shrink-0"
          />
          <span v-if="modelValue" class="truncate">{{ modelValue }}</span>
          <span v-else class="text-muted-foreground">{{ placeholder }}</span>
        </div>
        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-80 p-0" align="start">
      <div class="flex flex-col">
        <div class="flex items-center border-b px-3 py-2">
          <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
          <Input
            v-model="search"
            placeholder="Search icons..."
            class="h-8 border-0 bg-transparent p-0 shadow-none focus-visible:ring-0"
          />
        </div>

        <div class="h-72 overflow-y-auto">
          <div class="p-2">
            <div v-if="modelValue" class="mb-2 flex items-center justify-between border-b pb-2">
              <div class="flex items-center gap-2 text-sm">
                <component
                  v-if="selectedIconComponent"
                  :is="selectedIconComponent"
                  class="h-4 w-4"
                />
                <span>{{ modelValue }}</span>
              </div>
              <Button variant="ghost" size="sm" class="h-6 px-2 text-xs" @click="clearIcon">
                Clear
              </Button>
            </div>

            <div v-if="isLoading" class="py-6 text-center text-sm text-muted-foreground">
              Loading icons...
            </div>

            <div
              v-else-if="filteredIcons.length === 0"
              class="py-6 text-center text-sm text-muted-foreground"
            >
              No icons found.
            </div>

            <div v-else class="grid grid-cols-8 gap-1">
              <button
                v-for="iconName in filteredIcons"
                :key="iconName"
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-none"
                :class="{
                  'bg-accent text-accent-foreground': pascalToKebab(iconName) === modelValue,
                }"
                :title="pascalToKebab(iconName)"
                @click="selectIcon(iconName)"
              >
                <component :is="getIconComponent(iconName)" class="h-4 w-4" />
              </button>
            </div>

            <p
              v-if="filteredIcons.length >= 150"
              class="mt-2 text-center text-xs text-muted-foreground"
            >
              Showing first 150 results. Type to search for more.
            </p>
          </div>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
