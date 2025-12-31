<?php

namespace App\Http\Controllers;

use App\Models\FeedbackForm;
use App\Models\Page;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    public function index(): Response
    {
        $defaultNav = Page::navigationTabs()
            ->published()
            ->where('is_default', true)
            ->first();

        if (! $defaultNav) {
            $defaultNav = Page::navigationTabs()->published()->first();
        }

        if (! $defaultNav) {
            abort(404, 'No documentation available');
        }

        $firstDoc = $this->findFirstDocument($defaultNav);

        if ($firstDoc) {
            return $this->show($firstDoc->getFullPath());
        }

        return $this->renderDocsPage($defaultNav, null);
    }

    public function show(string $path): Response
    {
        $segments = explode('/', $path);
        $page = $this->findPageByPath($segments);

        if (! $page || ! $page->isPublished()) {
            abort(404, 'Page not found');
        }

        return $this->renderDocsPage($page->getNavigationTab(), $page);
    }

    private function renderDocsPage(?Page $activeNav, ?Page $currentPage): Response
    {
        $navigationTabs = Page::navigationTabs()
            ->published()
            ->get(['id', 'title', 'slug', 'icon', 'is_default']);

        $sidebarItems = $activeNav
            ? $this->buildSidebarTree($activeNav)
            : [];

        $tableOfContents = $currentPage && $currentPage->isDocument()
            ? $this->extractTableOfContents($currentPage->content ?? '')
            : [];

        $breadcrumbs = $currentPage
            ? $this->buildBreadcrumbs($currentPage)
            : [];

        $pageData = null;
        if ($currentPage) {
            $pageData = $currentPage->only([
                'id', 'title', 'slug', 'type',
                'seo_title', 'seo_description', 'updated_at',
                'source', 'updated_at_git', 'git_last_author',
            ]);
            $pageData['content_raw'] = $currentPage->content;
            $pageData['content'] = $pageData['content_raw']
                ? Str::markdown($pageData['content_raw'], ['html_input' => 'strip', 'allow_unsafe_links' => false])
                : null;

            $pageData['edit_on_github_url'] = $currentPage->getEditOnGitHubUrlAttribute();
        }

        $feedbackForms = FeedbackForm::active()->get(['id', 'name', 'trigger_type', 'fields']);

        return Inertia::render('docs/Show', [
            'navigationTabs' => $navigationTabs,
            'activeNavId' => $activeNav?->id,
            'sidebarItems' => $sidebarItems,
            'currentPage' => $pageData,
            'tableOfContents' => $tableOfContents,
            'breadcrumbs' => $breadcrumbs,
            'feedbackForms' => $feedbackForms,
        ]);
    }

    private function findPageByPath(array $segments, ?int $parentId = null): ?Page
    {
        if (empty($segments)) {
            return null;
        }

        $slug = array_shift($segments);

        $query = Page::where('slug', $slug)->published();

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $page = $query->first();

        if (! $page) {
            return null;
        }

        if (empty($segments)) {
            return $page;
        }

        return $this->findPageByPath($segments, $page->id);
    }

    private function findFirstDocument(Page $parent): ?Page
    {
        $children = $parent->children()
            ->published()
            ->orderBy('order')
            ->get();

        foreach ($children as $child) {
            if ($child->isDocument()) {
                return $child;
            }

            if ($child->isGroup()) {
                $doc = $this->findFirstDocument($child);
                if ($doc) {
                    return $doc;
                }
            }
        }

        return null;
    }

    private function buildSidebarTree(Page $navigation): array
    {
        $allPages = Page::published()
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'type', 'icon', 'is_expanded', 'parent_id']);

        return $this->buildTreeFromCollection($allPages, $navigation->id);
    }

    private function buildTreeFromCollection($pages, int $parentId): array
    {
        $items = [];

        $children = $pages->where('parent_id', $parentId);

        foreach ($children as $child) {
            $item = [
                'id' => $child->id,
                'title' => $child->title,
                'slug' => $child->slug,
                'type' => $child->type,
                'icon' => $child->icon,
                'path' => $child->getFullPath(),
                'isExpanded' => $child->is_expanded,
            ];

            if ($child->isGroup()) {
                $item['children'] = $this->buildTreeFromCollection($pages, $child->id);
            }

            $items[] = $item;
        }

        return $items;
    }

    private function extractTableOfContents(string $content): array
    {
        $toc = [];

        preg_match_all('/^(#{1,3})\s+(.+)$/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = strlen($match[1]);
            $text = trim($match[2]);
            $id = $this->slugify($text);

            $toc[] = [
                'id' => $id,
                'text' => $text,
                'level' => $level,
            ];
        }

        return $toc;
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('/[^\w\s-]/', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);

        return strtolower(trim($text, '-'));
    }

    private function buildBreadcrumbs(Page $page): array
    {
        $breadcrumbs = [];
        $current = $page;

        while ($current) {
            array_unshift($breadcrumbs, [
                'title' => $current->title,
                'path' => $current->getFullPath(),
                'type' => $current->type,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }
}
