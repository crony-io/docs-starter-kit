<?php

namespace App\Http\Controllers;

use App\Models\FeedbackForm;
use App\Models\Page;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    /**
     * Determine the documentation structure mode:
     * - 'navigation': Has navigation tabs (full hierarchy)
     * - 'flat': No navigation tabs, but has root-level pages (simple flat structure)
     * - 'empty': No pages at all
     */
    private function getStructureMode(): string
    {
        $hasNavTabs = Page::navigationTabs()->published()->exists();
        if ($hasNavTabs) {
            return 'navigation';
        }

        $hasRootPages = Page::rootLevel()->published()->exists();
        if ($hasRootPages) {
            return 'flat';
        }

        return 'empty';
    }

    public function index(): Response
    {
        $mode = $this->getStructureMode();

        if ($mode === 'empty') {
            return $this->renderEmptyState();
        }

        if ($mode === 'navigation') {
            return $this->handleNavigationMode();
        }

        // Flat mode: no navigation tabs, just root-level pages
        return $this->handleFlatMode();
    }

    private function handleNavigationMode(): Response
    {
        $defaultNav = Page::navigationTabs()
            ->published()
            ->where('is_default', true)
            ->first();

        if (! $defaultNav) {
            $defaultNav = Page::navigationTabs()->published()->first();
        }

        $firstDoc = $this->findFirstDocument($defaultNav);

        if ($firstDoc) {
            return $this->show($firstDoc->getFullPath());
        }

        return $this->renderDocsPage($defaultNav, null, 'navigation');
    }

    private function handleFlatMode(): Response
    {
        // Find first document at root level or inside groups
        $firstDoc = Page::rootLevel()
            ->published()
            ->where('type', 'document')
            ->orderBy('order')
            ->first();

        if ($firstDoc) {
            return $this->show($firstDoc->getFullPath());
        }

        // No direct documents, check inside groups
        $rootGroups = Page::rootLevel()
            ->published()
            ->where('type', 'group')
            ->orderBy('order')
            ->get();

        foreach ($rootGroups as $group) {
            $doc = $this->findFirstDocument($group);
            if ($doc) {
                return $this->show($doc->getFullPath());
            }
        }

        // Has pages but no documents yet (only empty groups)
        return $this->renderDocsPage(null, null, 'flat');
    }

    private function renderEmptyState(): Response
    {
        $feedbackForms = FeedbackForm::active()->get(['id', 'name', 'trigger_type', 'fields']);

        return Inertia::render('docs/Show', [
            'navigationTabs' => [],
            'activeNavId' => null,
            'sidebarItems' => [],
            'currentPage' => null,
            'tableOfContents' => [],
            'breadcrumbs' => [],
            'feedbackForms' => $feedbackForms,
            'isEmpty' => true,
            'structureMode' => 'empty',
        ]);
    }

    public function show(string $path): Response
    {
        $segments = explode('/', $path);
        $page = $this->findPageByPath($segments);

        if (! $page || ! $page->isPublished()) {
            abort(404, 'Page not found');
        }

        $mode = $this->getStructureMode();

        return $this->renderDocsPage($page->getNavigationTab(), $page, $mode);
    }

    private function renderDocsPage(?Page $activeNav, ?Page $currentPage, string $mode = 'navigation'): Response
    {
        $navigationTabs = [];
        $sidebarItems = [];

        if ($mode === 'navigation') {
            // Full hierarchy mode with navigation tabs
            $navigationTabs = Page::navigationTabs()
                ->published()
                ->get(['id', 'title', 'slug', 'icon', 'is_default']);

            $sidebarItems = $activeNav
                ? $this->buildSidebarTree($activeNav)
                : [];
        } else {
            // Flat mode: show all root-level pages in sidebar
            $sidebarItems = $this->buildFlatSidebarTree();
        }

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
                'seo_title', 'seo_description', 'updated_at', 'created_at',
                'source', 'updated_at_git', 'git_last_author',
            ]);
            $pageData['content_raw'] = $currentPage->content;
            $pageData['content'] = $pageData['content_raw']
                ? Str::markdown($pageData['content_raw'], ['html_input' => 'strip', 'allow_unsafe_links' => false])
                : null;

            $pageData['edit_on_github_url'] = $currentPage->getEditOnGitHubUrlAttribute();
            $pageData['canonical_url'] = url('/docs/'.$currentPage->getFullPath());
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
            'structureMode' => $mode,
        ]);
    }

    /**
     * Build sidebar tree for flat mode (no navigation tabs)
     * Shows all root-level documents and groups with their children
     */
    private function buildFlatSidebarTree(): array
    {
        $allPages = Page::published()
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'type', 'icon', 'is_expanded', 'parent_id']);

        return Page::buildTreeFromCollection($allPages, null, function ($child) {
            return [
                'id' => $child->id,
                'title' => $child->title,
                'slug' => $child->slug,
                'type' => $child->type,
                'icon' => $child->icon,
                'path' => $child->getFullPath(),
                'isExpanded' => $child->is_expanded,
            ];
        });
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

        return Page::buildTreeFromCollection($allPages, $navigation->id, function ($child) {
            $item = [
                'id' => $child->id,
                'title' => $child->title,
                'slug' => $child->slug,
                'type' => $child->type,
                'icon' => $child->icon,
                'path' => $child->getFullPath(),
                'isExpanded' => $child->is_expanded,
            ];

            return $item;
        });
    }

    private function extractTableOfContents(string $content): array
    {
        $toc = [];
        $idCounts = [];

        preg_match_all('/^(#{1,3})\s+(.+)$/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = strlen($match[1]);
            $text = trim($match[2]);
            $baseId = $this->slugify($text);

            // Track occurrences and append suffix for duplicates
            $count = $idCounts[$baseId] ?? 0;
            $idCounts[$baseId] = $count + 1;

            $id = $count === 0 ? $baseId : "{$baseId}-{$count}";

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
