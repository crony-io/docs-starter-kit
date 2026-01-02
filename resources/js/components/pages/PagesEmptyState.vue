<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { create } from '@/routes/admin/pages';
import { Link } from '@inertiajs/vue3';
import { FileText, Plus } from 'lucide-vue-next';
</script>

<template>
  <div class="rounded-lg border bg-card py-12 text-center">
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
      <FileText class="h-6 w-6 text-muted-foreground" />
    </div>
    <h3 class="text-lg font-medium">No pages yet</h3>
    <p class="mt-1 text-sm text-muted-foreground">
      Get started by creating your first documentation page.
    </p>
    <Button as-child class="mt-4">
      <Link :href="create()">
        <Plus class="mr-2 h-4 w-4" />
        Create Page
      </Link>
    </Button>
  </div>
</template>
