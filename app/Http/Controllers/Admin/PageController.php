<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageStoreRequest;
use App\Http\Requests\Admin\PageUpdateRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Page::query()
            ->with(['author:id,name', 'parent:id,title,type'])
            ->withCount('children');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $pages = $query
            ->orderBy('order')
            ->paginate(config('pagination.pages', 20))
            ->withQueryString();

        $navigationTabs = Page::navigationTabs()->published()->get(['id', 'title', 'slug']);

        $treeData = $this->buildPagesTree();

        return Inertia::render('admin/pages/Index', [
            'pages' => $pages,
            'treeData' => $treeData,
            'navigationTabs' => $navigationTabs,
            'filters' => $request->only(['status', 'search', 'type', 'view']),
            'statuses' => [
                ['value' => 'all', 'label' => 'All Statuses'],
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'published', 'label' => 'Published'],
                ['value' => 'archived', 'label' => 'Archived'],
            ],
            'types' => [
                ['value' => 'all', 'label' => 'All Types'],
                ['value' => 'navigation', 'label' => 'Navigation Tab'],
                ['value' => 'group', 'label' => 'Group'],
                ['value' => 'document', 'label' => 'Document'],
            ],
        ]);
    }

    private function buildPagesTree(): array
    {
        $allPages = Page::query()
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'type', 'status', 'parent_id', 'updated_at']);

        return Page::buildTreeFromCollection($allPages, null, fn ($child) => [
            'id' => $child->id,
            'title' => $child->title,
            'slug' => $child->slug,
            'type' => $child->type,
            'status' => $child->status,
            'updated_at' => $child->updated_at->toISOString(),
        ]);
    }

    public function create(Request $request): Response
    {
        $parentId = $request->query('parent_id');
        $type = $request->query('type', 'document');

        $navigationTabs = Page::navigationTabs()->get(['id', 'title', 'slug']);

        $potentialParents = Page::query()
            ->whereIn('type', ['navigation', 'group'])
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'type', 'parent_id']);

        return Inertia::render('admin/pages/Create', [
            'navigationTabs' => $navigationTabs,
            'potentialParents' => $potentialParents,
            'defaultParentId' => $parentId ? (int) $parentId : null,
            'defaultType' => $type,
        ]);
    }

    public function store(PageStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();
        $validated['source'] = 'cms';
        $validated['order'] = Page::max('order') + 1;

        $page = Page::create($validated);

        return to_route('admin.pages.edit', $page)
            ->with('success', 'Page created successfully.');
    }

    public function edit(Page $page): Response
    {
        $page->load(['versions' => fn ($q) => $q->latest()->take(10), 'parent:id,title,type', 'children']);

        $navigationTabs = Page::navigationTabs()->get(['id', 'title', 'slug']);

        $potentialParents = Page::query()
            ->whereIn('type', ['navigation', 'group'])
            ->where('id', '!=', $page->id)
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'type', 'parent_id']);

        $pageData = $page->toArray();
        $pageData['full_path'] = $page->getFullPath();

        return Inertia::render('admin/pages/Edit', [
            'page' => $pageData,
            'navigationTabs' => $navigationTabs,
            'potentialParents' => $potentialParents,
        ]);
    }

    public function update(PageUpdateRequest $request, Page $page): RedirectResponse
    {
        $page->createVersion();

        $page->update($request->validated());

        return back()->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->children()->exists()) {
            return back()->with('error', 'Cannot delete a page with child pages.');
        }

        $page->delete();

        return to_route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    public function duplicate(Page $page): RedirectResponse
    {
        $newPage = $page->replicate();
        $newPage->title = $page->title.' (Copy)';
        $newPage->slug = $page->slug.'-copy-'.time();
        $newPage->status = 'draft';
        $newPage->order = Page::max('order') + 1;
        $newPage->created_by = auth()->id();
        $newPage->save();

        return to_route('admin.pages.edit', $newPage)
            ->with('success', 'Page duplicated successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'pages' => ['required', 'array'],
            'pages.*.id' => ['required', 'exists:pages,id'],
            'pages.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->pages as $pageData) {
            Page::where('id', $pageData['id'])->update(['order' => $pageData['order']]);
        }

        return back()->with('success', 'Pages reordered successfully.');
    }

    public function publish(Page $page): RedirectResponse
    {
        $page->publish();

        return back()->with('success', 'Page published successfully.');
    }

    public function unpublish(Page $page): RedirectResponse
    {
        $page->unpublish();

        return back()->with('success', 'Page unpublished successfully.');
    }

    public function restoreVersion(Page $page, int $versionId): RedirectResponse
    {
        $version = $page->versions()->findOrFail($versionId);

        $page->createVersion();

        $version->restore();

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Version restored successfully.');
    }
}
