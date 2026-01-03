<?php

namespace App\Services;

use App\Models\Page;

class ContentImporter
{
    private AssetUrlTransformer $assetTransformer;

    private PageImporterService $pageImporterService;

    public function __construct(PageImporterService $pageImporterService)
    {
        $this->assetTransformer = new AssetUrlTransformer;
        $this->pageImporterService = $pageImporterService;
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

        return $this->pageImporterService->syncDocument($parsedContent['slug'], $parentPage, [
            'git_path' => $parsedContent['git_path'],
            'source' => 'git',
            'title' => $parsedContent['title'],
            'content' => $content,
            'seo_title' => $parsedContent['seo_title'],
            'seo_description' => $parsedContent['seo_description'],
            'status' => $parsedContent['status'],
            'order' => $parsedContent['order'],
            'git_last_commit' => $commitInfo['sha'],
            'git_last_author' => $commitInfo['author'],
            'updated_at_git' => $commitInfo['date'],
        ]);
    }

    public function deleteRemovedPages(array $currentGitPaths): array
    {
        return $this->pageImporterService->cleanupMissingPages('git', $currentGitPaths);
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

        $navigation = $this->pageImporterService->syncNavigation($navigationSegment, [
            'source' => 'git',
            'git_path' => 'docs/'.$navigationSegment,
        ]);

        $parent = $navigation;
        $currentPath = [$navigationSegment];

        foreach ($segments as $segment) {
            $currentPath[] = $segment;
            $gitPath = 'docs/'.implode('/', $currentPath);

            $parent = $this->pageImporterService->syncGroup($segment, $parent, [
                'source' => 'git',
                'git_path' => $gitPath,
            ]);
        }

        return $parent;
    }
}
