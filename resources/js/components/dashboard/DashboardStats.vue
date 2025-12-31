<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as feedbackIndex } from '@/routes/admin/feedback';
import { index as pagesIndex } from '@/routes/admin/pages';
import { Link } from '@inertiajs/vue3';
import { FileText, MessageSquare, ThumbsUp } from 'lucide-vue-next';

interface Stats {
  totalPages: number;
  publishedPages: number;
  draftPages: number;
  totalFeedback: number;
  positiveFeedback: number;
}

defineProps<{ stats: Stats }>();
</script>

<template>
  <div class="grid gap-4 md:grid-cols-3">
    <Link :href="pagesIndex()" class="block transition-transform hover:scale-[1.02]">
      <Card class="cursor-pointer hover:border-primary/50">
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Total Pages</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <FileText class="h-5 w-5 text-muted-foreground" />
            <span class="text-2xl font-bold">{{ stats.totalPages }}</span>
          </div>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ stats.publishedPages }} published, {{ stats.draftPages }} drafts
          </p>
        </CardContent>
      </Card>
    </Link>

    <Link :href="feedbackIndex()" class="block transition-transform hover:scale-[1.02]">
      <Card class="cursor-pointer hover:border-primary/50">
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Feedback</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <MessageSquare class="h-5 w-5 text-muted-foreground" />
            <span class="text-2xl font-bold">{{ stats.totalFeedback }}</span>
          </div>
          <p class="mt-1 text-xs text-muted-foreground">total responses received</p>
        </CardContent>
      </Card>
    </Link>

    <Link :href="feedbackIndex()" class="block transition-transform hover:scale-[1.02]">
      <Card class="cursor-pointer hover:border-primary/50">
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Positive Rate</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <ThumbsUp class="h-5 w-5 text-green-500" />
            <span class="text-2xl font-bold text-green-600">
              {{
                stats.totalFeedback > 0
                  ? Math.round((stats.positiveFeedback / stats.totalFeedback) * 100)
                  : 0
              }}%
            </span>
          </div>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ stats.positiveFeedback }} helpful votes
          </p>
        </CardContent>
      </Card>
    </Link>
  </div>
</template>
