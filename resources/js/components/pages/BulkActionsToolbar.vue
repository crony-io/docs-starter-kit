<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Eye, EyeOff, Trash2, X } from 'lucide-vue-next';

interface Props {
  selectedCount: number;
  processing: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
  (e: 'clear'): void;
  (e: 'publish'): void;
  (e: 'unpublish'): void;
  (e: 'delete'): void;
}>();
</script>

<template>
  <div class="flex items-center gap-3 rounded-lg border bg-muted/50 px-4 py-2">
    <span class="text-sm font-medium">
      <template v-if="selectedCount > 0">
        {{ selectedCount }} {{ selectedCount === 1 ? 'item' : 'items' }} selected
      </template>
      <template v-else>
        <span class="text-muted-foreground">Click items to select</span>
      </template>
    </span>

    <template v-if="selectedCount > 0">
      <Separator orientation="vertical" class="h-6" />

      <div class="flex items-center gap-1">
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button
                variant="ghost"
                size="sm"
                :disabled="processing"
                class="text-green-600 hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-950"
                @click="emit('publish')"
              >
                <Eye class="mr-1.5 h-4 w-4" />
                Publish
              </Button>
            </TooltipTrigger>
            <TooltipContent>Publish selected items</TooltipContent>
          </Tooltip>

          <Tooltip>
            <TooltipTrigger as-child>
              <Button
                variant="ghost"
                size="sm"
                :disabled="processing"
                class="text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:hover:bg-amber-950"
                @click="emit('unpublish')"
              >
                <EyeOff class="mr-1.5 h-4 w-4" />
                Unpublish
              </Button>
            </TooltipTrigger>
            <TooltipContent>Unpublish selected items</TooltipContent>
          </Tooltip>

          <Tooltip>
            <TooltipTrigger as-child>
              <Button
                variant="ghost"
                size="sm"
                :disabled="processing"
                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                @click="emit('delete')"
              >
                <Trash2 class="mr-1.5 h-4 w-4" />
                Delete
              </Button>
            </TooltipTrigger>
            <TooltipContent>Delete selected items</TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <Separator orientation="vertical" class="mx-1 h-6" />

        <Button variant="ghost" size="sm" :disabled="processing" @click="emit('clear')">
          <X class="mr-1.5 h-4 w-4" />
          Clear
        </Button>
      </div>
    </template>
  </div>
</template>
