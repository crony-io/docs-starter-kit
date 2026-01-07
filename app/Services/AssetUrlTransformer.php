<?php

namespace App\Services;

use App\Models\SystemConfig;

class AssetUrlTransformer
{
    private ?string $rawBaseUrl = null;

    public function __construct()
    {
        $this->initializeBaseUrl();
    }

    private function initializeBaseUrl(): void
    {
        $config = SystemConfig::instance();

        if (! $config->git_repository_url || ! $config->git_branch) {
            return;
        }

        // Parse GitHub URL: https://github.com/owner/repo
        // Convert to raw URL: https://raw.githubusercontent.com/owner/repo/branch
        $repoUrl = rtrim($config->git_repository_url, '/');
        $branch = $config->git_branch;

        if (preg_match('#^https?://github\.com/([^/]+)/([^/]+)#', $repoUrl, $matches)) {
            $owner = $matches[1];
            $repo = str_replace('.git', '', $matches[2]);
            $this->rawBaseUrl = "https://raw.githubusercontent.com/{$owner}/{$repo}/{$branch}";
        }
    }

    public function transformContent(string $content, string $filePath): string
    {
        if (! $this->rawBaseUrl) {
            return $content;
        }

        // Get the directory of the current file for resolving relative paths
        $fileDir = dirname($filePath);

        // Transform markdown image syntax: ![alt](path)
        $content = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)]+)\)/',
            fn ($matches) => $this->transformImageMatch($matches, $fileDir),
            $content
        );

        // Transform HTML img tags: <img src="path">
        $content = preg_replace_callback(
            '/<img\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i',
            fn ($matches) => $this->transformHtmlImgMatch($matches, $fileDir),
            $content
        );

        return $content;
    }

    private function transformImageMatch(array $matches, string $fileDir): string
    {
        $alt = $matches[1];
        $path = $matches[2];

        $newPath = $this->transformAssetPath($path, $fileDir);

        return "![{$alt}]({$newPath})";
    }

    private function transformHtmlImgMatch(array $matches, string $fileDir): string
    {
        $beforeSrc = $matches[1];
        $path = $matches[2];
        $afterSrc = $matches[3];

        $newPath = $this->transformAssetPath($path, $fileDir);

        return "<img {$beforeSrc}src=\"{$newPath}\"{$afterSrc}>";
    }

    private function transformAssetPath(string $path, string $fileDir): string
    {
        // Skip if already an absolute URL
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        // Skip data URIs
        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        // Skip anchor links
        if (str_starts_with($path, '#')) {
            return $path;
        }

        // Resolve relative path
        if (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }

        // Handle parent directory references
        $resolvedPath = $this->resolvePath($fileDir, $path);

        return $this->rawBaseUrl.'/'.ltrim($resolvedPath, '/');
    }

    private function resolvePath(string $baseDir, string $relativePath): string
    {
        if (str_starts_with($relativePath, '/')) {
            return 'docs'.$relativePath;
        }

        $resolved = collect(explode('/', $baseDir.'/'.$relativePath))
            ->reduce(function (array $resolved, string $part) {
                if ($part === '' || $part === '.') {
                    return $resolved;
                }

                if ($part === '..') {
                    array_pop($resolved);

                    return $resolved;
                }

                $resolved[] = $part;

                return $resolved;
            }, []);

        return implode('/', $resolved);
    }

    public function getRawBaseUrl(): ?string
    {
        return $this->rawBaseUrl;
    }
}
