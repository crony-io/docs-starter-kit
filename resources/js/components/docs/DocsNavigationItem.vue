<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import type { SidebarItem } from '@/types/docs';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
  item: SidebarItem;
  currentPath: string;
  depth: number;
}

const props = defineProps<Props>();

const isOpen = ref(props.item.isExpanded);
const hasChildren = computed(() => props.item.children && props.item.children.length > 0);
const isActive = computed(() => props.currentPath === props.item.path);
const isActiveParent = computed(() => props.currentPath.startsWith(props.item.path + '/'));
</script>

<template>
  <SidebarMenuItem v-if="hasChildren">
    <Collapsible v-model:open="isOpen" class="group/collapsible">
      <CollapsibleTrigger as-child>
        <SidebarMenuButton :is-active="isActiveParent">
          <ChevronRight
            class="transition-transform group-data-[state=open]/collapsible:rotate-90"
          />
          <span>{{ item.title }}</span>
        </SidebarMenuButton>
      </CollapsibleTrigger>
      <CollapsibleContent>
        <SidebarMenuSub>
          <DocsNavigationItem
            v-for="child in item.children"
            :key="child.id"
            :item="child"
            :current-path="currentPath"
            :depth="depth + 1"
          />
        </SidebarMenuSub>
      </CollapsibleContent>
    </Collapsible>
  </SidebarMenuItem>

  <SidebarMenuSubItem v-else-if="depth > 0">
    <SidebarMenuSubButton as-child :is-active="isActive">
      <Link :href="`/docs/${item.path}`">
        <span>{{ item.title }}</span>
      </Link>
    </SidebarMenuSubButton>
  </SidebarMenuSubItem>

  <SidebarMenuItem v-else>
    <SidebarMenuButton as-child :is-active="isActive">
      <Link :href="`/docs/${item.path}`">
        <span>{{ item.title }}</span>
      </Link>
    </SidebarMenuButton>
  </SidebarMenuItem>
</template>
