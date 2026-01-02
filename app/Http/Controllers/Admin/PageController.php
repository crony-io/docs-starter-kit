<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageStoreRequest;
use App\Http\Requests\Admin\PageUpdateRequest;
use App\Models\Folder;
use App\Models\Media;
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

        $potentialParents = Page::query()
            ->whereIn('type', ['navigation', 'group'])
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'type', 'parent_id']);

        return Inertia::render('admin/pages/Index', [
            'pages' => $pages,
            'treeData' => $treeData,
            'navigationTabs' => $navigationTabs,
            'potentialParents' => $potentialParents,
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
            'mediaBrowser' => Inertia::optional(fn () => $this->getMediaBrowserData($request)),
        ]);
    }

    public function store(PageStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $fromDialog = $validated['from_dialog'] ?? false;
        unset($validated['from_dialog']);

        $validated['created_by'] = auth()->id();
        $validated['source'] = 'cms';
        $validated['order'] = Page::max('order') + 1;

        $page = Page::create($validated);

        if ($fromDialog) {
            return to_route('admin.pages.index')
                ->with('success', "{$page->title} created successfully.");
        }

        return to_route('admin.pages.edit', $page)
            ->with('success', 'Page created successfully.');
    }

    public function edit(Page $page, Request $request): Response
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
            'mediaBrowser' => Inertia::optional(fn () => $this->getMediaBrowserData($request)),
        ]);
    }

    private function getMediaBrowserData(Request $request): array
    {
        $query = Media::query()
            ->with(['folder', 'uploader:id,name'])
            ->latest();

        if ($request->filled('media_folder_id')) {
            $query->where('folder_id', $request->media_folder_id);
        } else {
            $query->whereNull('folder_id');
        }

        if ($request->filled('media_search')) {
            $query->where('name', 'like', '%'.$request->media_search.'%');
        }

        if ($request->filled('media_type')) {
            match ($request->media_type) {
                'image' => $query->images(),
                'document' => $query->documents(),
                'video' => $query->videos(),
                'audio' => $query->audios(),
                default => null,
            };
        }

        $files = $query->paginate(24)->withQueryString();

        $folders = Folder::query()
            ->when($request->filled('media_folder_id'),
                fn ($q) => $q->where('parent_id', $request->media_folder_id),
                fn ($q) => $q->whereNull('parent_id')
            )
            ->orderBy('name')
            ->get();

        $currentFolder = $request->filled('media_folder_id')
            ? Folder::with('parent')->find($request->media_folder_id)
            : null;

        $allFolders = Folder::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return [
            'files' => $files,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'allFolders' => $this->buildFolderTree($allFolders),
        ];
    }

    private function buildFolderTree($folders): array
    {
        return $folders->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'children' => $folder->children->count() > 0
                    ? $this->buildFolderTree($folder->children)
                    : [],
            ];
        })->toArray();
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

    public function move(Request $request, Page $page): RedirectResponse
    {
        $request->validate([
            'parent_id' => ['nullable', 'exists:pages,id'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $newParentId = $request->parent_id;

        // Navigation must stay at root level
        if ($page->type === 'navigation' && $newParentId) {
            return back()->with('error', 'Navigation tabs must stay at root level.');
        }

        // Documents cannot be at root level
        if ($page->type === 'document' && !$newParentId) {
            return back()->with('error', 'Documents must be inside a navigation or group.');
        }

        if ($newParentId) {
            $newParent = Page::find($newParentId);

            if (!$newParent) {
                return back()->with('error', 'Target parent not found.');
            }

            if ($newParent->id === $page->id) {
                return back()->with('error', 'Cannot move a page into itself.');
            }

            if ($newParent->type === 'document') {
                return back()->with('error', 'Cannot move pages into a document.');
            }

            $descendants = $this->getAllDescendantIds($page);
            if (in_array($newParentId, $descendants)) {
                return back()->with('error', 'Cannot move a page into its own descendant.');
            }
        }

        $page->parent_id = $newParentId;
        $page->order = $request->position;
        $page->save();

        $siblings = Page::where('parent_id', $newParentId)
            ->where('id', '!=', $page->id)
            ->orderBy('order')
            ->get();

        $order = 0;
        foreach ($siblings as $sibling) {
            if ($order === $request->position) {
                $order++;
            }
            $sibling->update(['order' => $order]);
            $order++;
        }

        return back()->with('success', 'Page moved successfully.');
    }

    private function getAllDescendantIds(Page $page): array
    {
        $ids = [];
        foreach ($page->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }

        return $ids;
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
