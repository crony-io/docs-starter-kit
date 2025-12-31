<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\SystemConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateLLMFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function handle(): void
    {
        $this->generateLLMsTxt();
        $this->generateLLMsFullTxt();
    }

    private function generateLLMsTxt(): void
    {
        $config = SystemConfig::instance();
        $siteName = config('app.name', 'Documentation');
        $siteUrl = config('app.url');

        $pages = Page::where('status', 'published')
            ->orderBy('order')
            ->get(['title', 'slug', 'seo_description']);

        $content = "# {$siteName}\n\n";
        $content .= "> Official documentation\n\n";
        $content .= "## Navigation\n\n";

        foreach ($pages as $page) {
            $url = "{$siteUrl}/docs/{$page->slug}";
            $content .= "- [{$page->title}]({$url})";
            if ($page->seo_description) {
                $content .= ": {$page->seo_description}";
            }
            $content .= "\n";
        }

        $content .= "\n## Last Updated\n\n";
        $content .= now()->toIso8601String()."\n";

        Storage::disk('public')->put('llms.txt', $content);
    }

    private function generateLLMsFullTxt(): void
    {
        $siteName = config('app.name', 'Documentation');
        $pages = Page::where('status', 'published')
            ->orderBy('order')
            ->get(['title', 'content', 'slug']);

        $content = "# {$siteName} - Complete Documentation\n\n";
        $content .= 'Generated: '.now()->toIso8601String()."\n\n";
        $content .= "---\n\n";

        foreach ($pages as $page) {
            $content .= "## {$page->title}\n\n";
            $content .= strip_tags($page->content)."\n\n";
            $content .= "---\n\n";
        }

        Storage::disk('public')->put('llms-full.txt', $content);
    }
}
