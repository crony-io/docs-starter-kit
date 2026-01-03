<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { vCspStyle } from '@/directives/vCspStyle';
import { cn } from '@/lib/utils';
import type { TocItem } from '@/types/docs';
import { List } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
  items: TocItem[];
}

defineProps<Props>();

const activeId = ref<string>('');
const mobileOpen = ref(false);

// Desktop sticky TOC refs
const asideRef = ref<HTMLElement | null>(null);
const tocContainerRef = ref<HTMLElement | null>(null);
const isSticky = ref(false);
const stickyOffset = 24; // Distance from viewport top when sticky
const tocTop = ref(0);
const tocMaxHeight = ref('auto');

const handleScroll = () => {
  // Update active heading
  const headings = document.querySelectorAll('h1[id], h2[id], h3[id]');
  let current = '';

  headings.forEach((heading) => {
    const rect = heading.getBoundingClientRect();
    if (rect.top <= 100) {
      current = heading.id;
    }
  });

  activeId.value = current;

  // Handle sticky TOC behavior
  if (!asideRef.value || !tocContainerRef.value) {
    return;
  }

  const aside = asideRef.value;
  const asideRect = aside.getBoundingClientRect();
  const parentRect = aside.parentElement?.getBoundingClientRect();

  if (!parentRect) {
    return;
  }

  // Calculate available height for TOC
  const viewportHeight = window.innerHeight;
  const availableHeight = viewportHeight - stickyOffset - 24; // 24px bottom padding

  tocMaxHeight.value = `${availableHeight}px`;

  // Determine if TOC should be sticky
  // Sticky when the aside's top would scroll above the sticky threshold
  if (asideRect.top <= stickyOffset) {
    // Check if we've scrolled past the content (parent bottom)
    const parentBottom = parentRect.bottom;
    const tocHeight = tocContainerRef.value.offsetHeight;

    if (parentBottom < stickyOffset + tocHeight) {
      // Pin to bottom of parent
      isSticky.value = false;
      tocTop.value = parentRect.height - tocHeight;
    } else {
      isSticky.value = true;
      tocTop.value = 0;
    }
  } else {
    isSticky.value = false;
    tocTop.value = 0;
  }
};

const tocStyle = computed((): Record<string, string> => {
  const style: Record<string, string> = {};

  if (tocMaxHeight.value !== 'auto') {
    style['max-height'] = tocMaxHeight.value;
  }

  if (isSticky.value) {
    style.position = 'fixed';
    style.top = `${stickyOffset}px`;
  } else if (tocTop.value > 0) {
    style.position = 'absolute';
    style.top = `${tocTop.value}px`;
  }

  return style;
});

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
  window.addEventListener('resize', handleScroll, { passive: true });
  handleScroll();
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', handleScroll);
});

const scrollTo = (id: string) => {
  const element = document.getElementById(id);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    mobileOpen.value = false;
  }
};

const getIndentClass = (level: number) => {
  if (level === 2) {
    return 'pl-3';
  }
  if (level === 3) {
    return 'pl-6';
  }
  return '';
};
</script>

<template>
  <!-- Mobile TOC Button - Teleported to body to avoid z-index/overflow issues -->
  <Teleport to="body">
    <div v-if="items.length > 0" class="fixed right-4 bottom-4 z-50 lg:hidden">
      <Sheet v-model:open="mobileOpen">
        <SheetTrigger as-child>
          <Button size="icon" class="h-12 w-12 rounded-full shadow-lg">
            <List class="h-5 w-5" />
            <span class="sr-only">Table of contents</span>
          </Button>
        </SheetTrigger>
        <SheetContent side="right" class="w-72 overflow-y-auto">
          <SheetHeader>
            <SheetTitle>On this page</SheetTitle>
          </SheetHeader>
          <ul class="space-y-1 px-4 pb-4">
            <li v-for="item in items" :key="item.id" :class="getIndentClass(item.level)">
              <button
                type="button"
                :class="
                  cn(
                    'h-auto w-full rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent',
                    activeId === item.id
                      ? 'font-medium text-primary'
                      : 'font-normal text-muted-foreground',
                  )
                "
                @click="scrollTo(item.id)"
              >
                {{ item.text }}
              </button>
            </li>
          </ul>
        </SheetContent>
      </Sheet>
    </div>
  </Teleport>

  <!-- Desktop TOC Sidebar -->
  <aside v-if="items.length > 0" ref="asideRef" class="relative hidden w-56 shrink-0 lg:block">
    <div
      ref="tocContainerRef"
      v-csp-style="tocStyle"
      class="scrollbar w-56 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6"
    >
      <h4 class="mb-3 text-sm font-semibold">On this page</h4>
      <ul class="space-y-1">
        <li v-for="item in items" :key="item.id" :class="getIndentClass(item.level)">
          <Button
            variant="ghost"
            size="sm"
            :class="
              cn(
                'h-auto w-full justify-start px-2 py-1 text-left text-sm whitespace-normal',
                activeId === item.id
                  ? 'font-medium text-primary'
                  : 'font-normal text-muted-foreground',
              )
            "
            @click="scrollTo(item.id)"
          >
            {{ item.text }}
          </Button>
        </li>
      </ul>
    </div>
  </aside>
</template>
