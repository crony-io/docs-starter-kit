<?php

namespace App\Services;

use App\Models\Page;

class ContentImporter
{
    public function import(array $parsedContent, array $commitInfo): Page
    {
        $hierarchy = $parsedContent['hierarchy'];
        $parentPage = $this->findOrCreateParents($hierarchy);

        $page = Page::updateOrCreate(
            [
                'git_path' => $parsedContent['git_path'],
                'source' => 'git',
            ],
            [
                'title' => $parsedContent['title'],
                'slug' => $parsedContent['slug'],
                'type' => 'document',
                'content' => $parsedContent['content'],
                'seo_title' => $parsedContent['seo_title'],
                'seo_description' => $parsedContent['seo_description'],
                'status' => $parsedContent['status'],
                'order' => $parsedContent['order'],
                'parent_id' => $parentPage?->id,
                'git_last_commit' => $commitInfo['sha'],
                'git_last_author' => $commitInfo['author'],
                'updated_at_git' => $commitInfo['date'],
            ]
        );

        return $page;
    }

    public function deleteRemovedPages(array $currentGitPaths): array
    {
        $deletedDocuments = Page::query()
            ->where('source', 'git')
            ->where('type', 'document')
            ->whereNotIn('git_path', $currentGitPaths)
            ->delete();

        $deletedGroups = 0;

        do {
            $deletedThisPass = Page::query()
                ->where('source', 'git')
                ->where('type', 'group')
                ->whereDoesntHave('children')
                ->delete();

            $deletedGroups += $deletedThisPass;
        } while ($deletedThisPass > 0);

        $deletedNavigation = Page::query()
            ->where('source', 'git')
            ->where('type', 'navigation')
            ->whereDoesntHave('children')
            ->delete();

        return [
            'documents' => $deletedDocuments,
            'groups' => $deletedGroups,
            'navigation' => $deletedNavigation,
        ];
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

        $navigation = Page::firstOrCreate(
            [
                'slug' => $navigationSegment,
                'source' => 'git',
                'parent_id' => null,
            ],
            [
                'title' => str_replace(['-', '_'], ' ', ucwords($navigationSegment)),
                'type' => 'navigation',
                'content' => '',
                'status' => 'published',
                'order' => 0,
                'is_default' => ! Page::where('type', 'navigation')->where('source', 'git')->exists(),
                'is_expanded' => true,
                'git_path' => 'docs/'.$navigationSegment,
            ]
        );

        $parent = $navigation;
        $currentPath = [$navigationSegment];

        foreach ($segments as $segment) {
            $currentPath[] = $segment;
            $gitPath = 'docs/'.implode('/', $currentPath);

            $parent = Page::firstOrCreate(
                [
                    'slug' => $segment,
                    'source' => 'git',
                    'parent_id' => $parent->id,
                ],
                [
                    'title' => str_replace(['-', '_'], ' ', ucwords($segment)),
                    'type' => 'group',
                    'content' => '',
                    'status' => 'published',
                    'order' => 0,
                    'is_expanded' => true,
                    'git_path' => $gitPath,
                ]
            );
        }

        return $parent;
    }
}
