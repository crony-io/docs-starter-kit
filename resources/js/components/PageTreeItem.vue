<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { create, duplicate, edit, publish, unpublish } from '@/routes/admin/pages';
import { Link, router } from '@inertiajs/vue3';
import {
  Book,
  ChevronRight,
  Copy,
  Eye,
  EyeOff,
  FileText,
  FolderTree,
  MoreHorizontal,
  Pencil,
  Plus,
  Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface TreeItem {
  id: number;
  title: string;
  slug: string;
  type: 'navigation' | 'group' | 'document';
  status: 'draft' | 'published' | 'archived';
  updated_at: string;
  children: TreeItem[];
}

interface Props {
  item: TreeItem;
  depth?: number;
}

interface Emits {
  (e: 'delete', item: TreeItem): void;
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
});

const emit = defineEmits<Emits>();

const typeIcons = {
  navigation: Book,
  group: FolderTree,
  document: FileText,
};

const isOpen = ref(props.item.children.length > 0);
const processingId = ref<number | null>(null);

const handleDuplicate = () => {
  processingId.value = props.item.id;
  router.post(
    duplicate(props.item.id).url,
    {},
    {
      onFinish: () => {
        processingId.value = null;
      },
    },
  );
};

const handlePublish = () => {
  processingId.value = props.item.id;
  router.post(
    publish(props.item.id).url,
    {},
    {
      onFinish: () => {
        processingId.value = null;
      },
    },
  );
};

const handleUnpublish = () => {
  processingId.value = props.item.id;
  router.post(
    unpublish(props.item.id).url,
    {},
    {
      onFinish: () => {
        processingId.value = null;
      },
    },
  );
};
</script>

<template>
  <Collapsible v-model:open="isOpen" class="group">
    <div
      class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-accent"
      :class="[
        depth === 0 && 'pl-2',
        depth === 1 && 'pl-6',
        depth === 2 && 'pl-10',
        depth === 3 && 'pl-14',
        depth >= 4 && 'pl-18',
      ]"
    >
      <CollapsibleTrigger
        v-if="item.children.length > 0"
        class="flex h-6 w-6 items-center justify-center rounded hover:bg-accent"
      >
        <ChevronRight class="h-4 w-4 transition-transform" :class="{ 'rotate-90': isOpen }" />
      </CollapsibleTrigger>
      <div v-else class="h-6 w-6" />

      <component :is="typeIcons[item.type]" class="h-4 w-4 text-muted-foreground" />

      <h2 class="flex-1 truncate text-sm font-medium hover:underline">{{ item.title }}</h2>

      <StatusBadge :status="item.status" class="text-xs" />

      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            size="icon"
            class="h-7 w-7 opacity-0 group-hover:opacity-100"
            :disabled="processingId === item.id"
          >
            <MoreHorizontal class="h-4 w-4" />
            <span class="sr-only">Actions</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem as-child>
            <Link :href="edit(item.id)" class="flex items-center">
              <Pencil class="mr-2 h-4 w-4" />
              Edit
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem @click="handleDuplicate">
            <Copy class="mr-2 h-4 w-4" />
            Duplicate
          </DropdownMenuItem>
          <DropdownMenuSeparator v-if="item.type !== 'document'" />
          <DropdownMenuItem v-if="item.type !== 'document'" as-child>
            <Link :href="create({ query: { parent_id: item.id } })" class="flex items-center">
              <Plus class="mr-2 h-4 w-4" />
              Add Child Page
            </Link>
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem v-if="item.status !== 'published'" @click="handlePublish">
            <Eye class="mr-2 h-4 w-4" />
            Publish
          </DropdownMenuItem>
          <DropdownMenuItem v-if="item.status === 'published'" @click="handleUnpublish">
            <EyeOff class="mr-2 h-4 w-4" />
            Unpublish
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            class="text-destructive focus:text-destructive"
            @click="emit('delete', item)"
          >
            <Trash2 class="mr-2 h-4 w-4" />
            Delete
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <CollapsibleContent v-if="item.children.length > 0">
      <PageTreeItem
        v-for="child in item.children"
        :key="child.id"
        :item="child"
        :depth="depth + 1"
        @delete="emit('delete', $event)"
      />
    </CollapsibleContent>
  </Collapsible>
</template>
