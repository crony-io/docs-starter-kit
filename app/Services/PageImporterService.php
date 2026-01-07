<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Str;

class PageImporterService
{
    /**
     * Create or update a navigation tab (top-level).
     */
    public function syncNavigation(string $slug, array $attributes): Page
    {
        return Page::updateOrCreate(
            [
                'slug' => $slug,
                'source' => $attributes['source'] ?? 'cms',
                'parent_id' => null,
            ],
            array_merge([
                'type' => 'navigation',
                'title' => Str::title(str_replace(['-', '_'], ' ', $slug)),
                'status' => 'published',
                'is_expanded' => true,
            ], array_filter($attributes, fn ($value) => $value !== null))
        );
    }

    /**
     * Create or update a group page.
     */
    public function syncGroup(string $slug, ?Page $parent, array $attributes): Page
    {
        return Page::updateOrCreate(
            [
                'slug' => $slug,
                'source' => $attributes['source'] ?? 'cms',
                'parent_id' => $parent?->id,
            ],
            array_merge([
                'type' => 'group',
                'title' => Str::title(str_replace(['-', '_'], ' ', $slug)),
                'status' => 'published',
                'is_expanded' => true,
            ], array_filter($attributes, fn ($value) => $value !== null))
        );
    }

    /**
     * Create or update a document page.
     */
    public function syncDocument(string $slug, ?Page $parent, array $attributes): Page
    {
        $matchAttributes = [
            'slug' => $slug,
            'source' => $attributes['source'] ?? 'cms',
            'parent_id' => $parent?->id,
        ];

        // For Git content, identity is the file path, not the slug/parent
        if (($attributes['source'] ?? '') === 'git' && ! empty($attributes['git_path'])) {
            $matchAttributes = [
                'git_path' => $attributes['git_path'],
                'source' => 'git',
            ];
        }

        return Page::updateOrCreate(
            $matchAttributes,
            array_merge([
                'type' => 'document',
                'status' => 'published',
                // Ensure slug and parent are updated/set regardless of match criteria
                'slug' => $slug,
                'parent_id' => $parent?->id,
            ], $attributes)
        );
    }

    /**
     * Delete pages from a specific source that are not in the kept list.
     * Useful for syncing to remove deleted files.
     */
    public function cleanupMissingPages(string $source, array $keptGitPaths): array
    {
        $deletedDocuments = Page::query()
            ->where('source', $source)
            ->where('type', 'document')
            ->whereNotIn('git_path', $keptGitPaths)
            ->delete();

        $orphanedStats = $this->cleanupOrphanedContainers($source);

        return [
            'documents' => $deletedDocuments,
            'groups' => $orphanedStats['groups'],
            'navigation' => $orphanedStats['navigation'],
        ];
    }

    /**
     * Remove groups and navigation tabs that have no children.
     */
    public function cleanupOrphanedContainers(string $source): array
    {
        // Cleanup groups that became empty
        $deletedGroups = 0;
        do {
            $idsToDelete = Page::query()
                ->where('source', $source)
                ->where('type', 'group')
                ->whereDoesntHave('children')
                ->pluck('id');
            $deletedThisPass = Page::whereIn('id', $idsToDelete)->delete();
            $deletedGroups += $deletedThisPass;
        } while ($deletedThisPass > 0);

        // Cleanup navigation that became empty
        $navIdsToDelete = Page::query()
            ->where('source', $source)
            ->where('type', 'navigation')
            ->whereDoesntHave('children')
            ->pluck('id');
        $deletedNavigation = Page::whereIn('id', $navIdsToDelete)->delete();

        return [
            'groups' => $deletedGroups,
            'navigation' => $deletedNavigation,
        ];
    }
}
