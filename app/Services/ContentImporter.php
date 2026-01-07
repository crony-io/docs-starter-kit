<?php

namespace App\Services;

use App\Models\Page;

class ContentImporter
{
    private AssetUrlTransformer $assetTransformer;

    private PageImporterService $pageImporterService;

    private array $metaData = [];

    public function __construct(PageImporterService $pageImporterService)
    {
        $this->assetTransformer = new AssetUrlTransformer;
        $this->pageImporterService = $pageImporterService;
    }

    public function setMetaData(array $metaData): void
    {
        $this->metaData = $metaData;
    }

    public function import(array $parsedContent, array $commitInfo): Page
    {
        $hierarchy = $parsedContent['hierarchy'];
        $parentPage = $this->findOrCreateParents($hierarchy);

        // Transform asset URLs to GitHub raw URLs
        $content = $this->assetTransformer->transformContent(
            $parsedContent['content'],
            $parsedContent['git_path']
        );

        // Get document meta from _meta.json (for ordering/title if not in frontmatter)
        $docMeta = $this->getDocumentMeta($hierarchy);

        // Use frontmatter order if set, otherwise use _meta.json order
        $order = $parsedContent['order'] ?? $docMeta['order'] ?? 0;

        // Use frontmatter title if set, otherwise use _meta.json title
        $title = $parsedContent['title'] ?? $docMeta['title'] ?? null;

        return $this->pageImporterService->syncDocument($parsedContent['slug'], $parentPage, [
            'git_path' => $parsedContent['git_path'],
            'source' => 'git',
            'title' => $title,
            'content' => $content,
            'seo_title' => $parsedContent['seo_title'],
            'seo_description' => $parsedContent['seo_description'],
            'status' => $parsedContent['status'],
            'order' => $order,
            'git_last_commit' => $commitInfo['sha'],
            'git_last_author' => $commitInfo['author'],
            'updated_at_git' => $commitInfo['date'],
        ]);
    }

    public function deleteRemovedPages(array $currentGitPaths): array
    {
        return $this->pageImporterService->cleanupMissingPages('git', $currentGitPaths);
    }

    public function updateNavigationOrdering(): void
    {
        collect($this->metaData)
            ->except('_root')
            ->each(function (array $meta, string $path) {
                $slug = basename($path);
                $pathParts = explode('/', $path);

                if (count($pathParts) === 2 && $pathParts[0] === 'docs') {
                    Page::where('slug', $slug)
                        ->where('type', 'navigation')
                        ->where('source', 'git')
                        ->update([
                            'order' => $meta['order'] ?? 0,
                            'title' => $meta['title'] ?? null,
                            'seo_description' => $meta['description'] ?? null,
                            'icon' => $meta['icon'] ?? null,
                            'is_default' => $meta['is_default'] ?? false,
                        ]);
                }

                if (isset($meta['items'])) {
                    collect($meta['items'])->each(function (array $itemMeta, string $itemSlug) {
                        Page::where('slug', $itemSlug)
                            ->where('source', 'git')
                            ->whereIn('type', ['group', 'document'])
                            ->update([
                                'order' => $itemMeta['order'] ?? 0,
                                'title' => $itemMeta['title'] ?? null,
                            ]);
                    });
                }
            });
    }

    public function cleanupOrphanedPages(): array
    {
        return $this->pageImporterService->cleanupOrphanedContainers('git');
    }

    private function findOrCreateParents(array $hierarchy): ?Page
    {
        if (count($hierarchy) === 0) {
            return null;
        }

        $segments = $hierarchy;

        // The last segment is the current document (file name)
        if (count($segments) > 0) {
            array_pop($segments);
        }

        if (count($segments) === 0) {
            return null;
        }

        $navigationSegment = array_shift($segments);
        $navMeta = $this->getNavigationMeta($navigationSegment);

        $navigation = $this->pageImporterService->syncNavigation($navigationSegment, [
            'source' => 'git',
            'git_path' => 'docs/'.$navigationSegment,
            'title' => $navMeta['title'] ?? null,
            'seo_description' => $navMeta['description'] ?? null,
            'icon' => $navMeta['icon'] ?? null,
            'order' => $navMeta['order'] ?? 0,
            'is_default' => $navMeta['is_default'] ?? false,
        ]);

        $parent = $navigation;
        $currentPath = [$navigationSegment];

        foreach ($segments as $segment) {
            $currentPath[] = $segment;
            $gitPath = 'docs/'.implode('/', $currentPath);
            $groupMeta = $this->getGroupMeta($currentPath, $segment);

            $parent = $this->pageImporterService->syncGroup($segment, $parent, [
                'source' => 'git',
                'git_path' => $gitPath,
                'title' => $groupMeta['title'] ?? null,
                'seo_description' => $groupMeta['description'] ?? null,
                'icon' => $groupMeta['icon'] ?? null,
                'order' => $groupMeta['order'] ?? 0,
            ]);
        }

        return $parent;
    }

    private function getNavigationMeta(string $slug): array
    {
        // Get data from the navigation's own _meta.json
        $metaPath = 'docs/'.$slug;
        if (isset($this->metaData[$metaPath])) {
            return $this->metaData[$metaPath];
        }

        return [];
    }

    private function getGroupMeta(array $currentPath, string $segment): array
    {
        // Check parent's _meta.json for this group's info
        $parentPath = 'docs/'.implode('/', array_slice($currentPath, 0, -1));

        if (isset($this->metaData[$parentPath]['items'][$segment])) {
            return $this->metaData[$parentPath]['items'][$segment];
        }

        // Check the group's own _meta.json
        $groupPath = 'docs/'.implode('/', $currentPath);
        if (isset($this->metaData[$groupPath])) {
            return $this->metaData[$groupPath];
        }

        return [];
    }

    private function getDocumentMeta(array $hierarchy): array
    {
        if (count($hierarchy) < 2) {
            return [];
        }

        // Get parent path and document slug
        $docSlug = end($hierarchy);
        $parentPath = 'docs/'.implode('/', array_slice($hierarchy, 0, -1));

        if (isset($this->metaData[$parentPath]['items'][$docSlug])) {
            return $this->metaData[$parentPath]['items'][$docSlug];
        }

        return [];
    }
}
