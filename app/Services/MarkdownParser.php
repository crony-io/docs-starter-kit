<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class MarkdownParser
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $environment = $this->converter->getEnvironment();
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new TaskListExtension);
    }

    public function parse(string $markdown, string $filePath): array
    {
        $document = YamlFrontMatter::parse($markdown);

        $frontmatter = $document->matter();
        $body = $document->body();

        return [
            'title' => $frontmatter['title'] ?? $this->titleFromPath($filePath),
            'slug' => $frontmatter['slug'] ?? $this->slugFromPath($filePath),
            'content' => $body,
            'seo_title' => $frontmatter['seo_title'] ?? null,
            'seo_description' => $frontmatter['description'] ?? null,
            'order' => $frontmatter['order'] ?? 0,
            'status' => $frontmatter['status'] ?? 'published',
            'hierarchy' => $this->extractHierarchy($filePath),
            'git_path' => $filePath,
        ];
    }

    public function renderToHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    public function parseMetaFile(string $jsonContent): array
    {
        return json_decode($jsonContent, true) ?? [];
    }

    private function extractHierarchy(string $filePath): array
    {
        // Convert: docs/getting-started/installation.md
        // To: ['getting-started', 'installation']
        $path = str_replace('docs/', '', $filePath);
        $path = str_replace('.md', '', $path);

        return array_filter(explode('/', $path));
    }

    private function titleFromPath(string $filePath): string
    {
        $filename = basename($filePath, '.md');

        return str_replace(['-', '_'], ' ', ucwords($filename));
    }

    private function slugFromPath(string $filePath): string
    {
        $hierarchy = $this->extractHierarchy($filePath);

        return (string) (end($hierarchy) ?: '');
    }
}
