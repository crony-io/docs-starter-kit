<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LocalDocsImporter
{
    private const RESERVED_FOLDERS = ['assets'];

    private MarkdownParser $parser;

    private PageImporterService $pageImporterService;

    private string $basePath;

    private array $metaCache = [];

    public function __construct(MarkdownParser $parser, PageImporterService $pageImporterService)
    {
        $this->parser = $parser;
        $this->pageImporterService = $pageImporterService;
        $this->basePath = base_path('docs');
    }

    public function import(): array
    {
        $stats = [
            'navigation' => 0,
            'groups' => 0,
            'documents' => 0,
            'errors' => [],
        ];

        if (! File::isDirectory($this->basePath)) {
            $stats['errors'][] = "Documentation directory not found: {$this->basePath}";

            return $stats;
        }

        $directories = collect(File::directories($this->basePath))
            ->filter(fn ($dir) => ! in_array(basename($dir), self::RESERVED_FOLDERS, true));

        foreach ($directories as $navDir) {
            try {
                $this->importNavigationTab($navDir, $stats);
            } catch (\Exception $e) {
                $stats['errors'][] = "Failed to import {$navDir}: ".$e->getMessage();
            }
        }

        return $stats;
    }

    private function importNavigationTab(string $navDir, array &$stats): void
    {
        $navSlug = basename($navDir);
        $navMeta = $this->loadMeta($navDir);

        $attributes = [
            'icon' => $navMeta['icon'] ?? 'file-text',
            'order' => $navMeta['order'] ?? 0,
            'is_default' => $navMeta['is_default'] ?? false,
            'source' => 'cms',
        ];

        if (isset($navMeta['title'])) {
            $attributes['title'] = $navMeta['title'];
        }

        $navigation = $this->pageImporterService->syncNavigation($navSlug, $attributes);

        $stats['navigation']++;

        $this->importChildren($navDir, $navigation, $navMeta, $stats);
    }

    private function importChildren(string $directory, Page $parent, array $parentMeta, array &$stats): void
    {
        $items = File::directories($directory);
        $files = File::files($directory);

        foreach ($items as $itemPath) {
            $slug = basename($itemPath);

            if ($slug === '_meta.json') {
                continue;
            }

            $this->importGroup($itemPath, $parent, $parentMeta, $stats);
        }

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if ($filename === '_meta.json' || ! Str::endsWith($filename, '.md')) {
                continue;
            }

            $this->importDocument($file->getPathname(), $parent, $parentMeta, $stats);
        }
    }

    private function importGroup(string $groupDir, Page $parent, array $parentMeta, array &$stats): void
    {
        $groupSlug = basename($groupDir);
        $groupMeta = $this->loadMeta($groupDir);

        $itemMeta = $parentMeta['items'][$groupSlug] ?? [];
        $order = $itemMeta['order'] ?? $groupMeta['order'] ?? 0;

        $attributes = [
            'icon' => $groupMeta['icon'] ?? null,
            'order' => $order,
            'is_expanded' => $groupMeta['is_expanded'] ?? true,
            'source' => 'cms',
        ];

        // Priority: parent item meta > group meta > default (null, handled by service)
        if (isset($itemMeta['title'])) {
            $attributes['title'] = $itemMeta['title'];
        } elseif (isset($groupMeta['title'])) {
            $attributes['title'] = $groupMeta['title'];
        }

        $group = $this->pageImporterService->syncGroup($groupSlug, $parent, $attributes);

        $stats['groups']++;

        $this->importChildren($groupDir, $group, $groupMeta, $stats);
    }

    private function importDocument(string $filePath, Page $parent, array $parentMeta, array &$stats): void
    {
        $filename = basename($filePath, '.md');
        $content = File::get($filePath);

        $parsed = $this->parser->parse($content, $filename.'.md');

        $itemMeta = $parentMeta['items'][$filename] ?? [];
        $order = $itemMeta['order'] ?? $parsed['order'] ?? 0;
        $title = $itemMeta['title'] ?? $parsed['title'];

        $status = $parsed['status'] ?? 'published';

        $this->pageImporterService->syncDocument($filename, $parent, [
            'title' => $title,
            'content' => $parsed['content'],
            'status' => $status,
            'order' => $order,
            'seo_title' => $parsed['seo_title'],
            'seo_description' => $parsed['seo_description'],
            'source' => 'cms',
        ]);

        $stats['documents']++;
    }

    private function loadMeta(string $directory): array
    {
        $metaPath = $directory.'/_meta.json';

        if (isset($this->metaCache[$metaPath])) {
            return $this->metaCache[$metaPath];
        }

        if (File::exists($metaPath)) {
            $content = File::get($metaPath);
            $meta = json_decode($content, true) ?? [];
            $this->metaCache[$metaPath] = $meta;

            return $meta;
        }

        return [];
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setBasePath(string $path): self
    {
        $this->basePath = $path;

        return $this;
    }

    public function hasDocumentation(): bool
    {
        return File::isDirectory($this->basePath)
            && count(File::directories($this->basePath)) > 0;
    }
}
