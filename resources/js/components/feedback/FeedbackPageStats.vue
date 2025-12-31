<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import type { PageStat } from '@/types/feedback';

defineProps<{ pageStats: PageStat[] }>();
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Page Helpfulness Scores</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-3">
        <div v-for="stat in pageStats" :key="stat.page_id" class="flex items-center gap-4">
          <div class="flex-1 truncate text-sm font-medium">{{ stat.page_title }}</div>
          <div class="w-32">
            <Progress :model-value="stat.helpfulness_score" class="h-2" />
          </div>
          <div class="w-16 text-right text-sm text-muted-foreground">
            {{ stat.helpfulness_score }}%
          </div>
          <div class="w-20 text-right text-xs text-muted-foreground">
            {{ stat.total }} responses
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
