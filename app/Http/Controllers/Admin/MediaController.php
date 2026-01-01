<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Models\Folder;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Media::query()
            ->with(['folder', 'uploader:id,name'])
            ->latest();

        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->folder_id);
        } else {
            $query->whereNull('folder_id');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('type')) {
            match ($request->type) {
                'image' => $query->images(),
                'document' => $query->documents(),
                'video' => $query->videos(),
                'audio' => $query->audios(),
                default => null,
            };
        }

        $files = $query->paginate(config('pagination.media', 24))->withQueryString();

        $folders = Folder::query()
            ->when($request->filled('folder_id'),
                fn ($q) => $q->where('parent_id', $request->folder_id),
                fn ($q) => $q->whereNull('parent_id')
            )
            ->orderBy('name')
            ->get();

        $currentFolder = $request->filled('folder_id')
            ? Folder::with('parent')->find($request->folder_id)
            : null;

        $allFolders = Folder::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/media/Index', [
            'files' => $files,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'filters' => $request->only(['folder_id', 'search', 'type']),
            'allFolders' => $this->buildFolderTree($allFolders),
        ]);
    }

    public function store(MediaUploadRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $user = auth()->user();
        $folderId = $request->input('folder_id');

        $media = $user->addMedia($file)
            ->usingFileName($this->generateFileName($file))
            ->withCustomProperties([
                'original_name' => $file->getClientOriginalName(),
            ])
            ->toMediaCollection('uploads');

        $media->update([
            'folder_id' => $folderId,
            'uploaded_by' => $user->id,
        ]);

        $media->refresh();

        return back()->with('file', $media)->with('success', 'File uploaded successfully.');
    }

    public function allFolders(): Response
    {
        $folders = Folder::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/media/Index', [
            'allFolders' => $this->buildFolderTree($folders),
        ]);
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

    public function show(Media $media): RedirectResponse
    {
        $media->load(['folder', 'uploader:id,name']);

        return back()->with('file', $media)->with('success', 'File uploaded successfully.');
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:folders,id'],
        ]);

        $media->update($validated);

        return back()->with('file', $media)->with('success', 'File updated successfully.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $media->delete();

        return back()->with('success', 'File deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'exists:media,id'],
        ]);

        Media::whereIn('id', $validated['ids'])->delete();

        return back()->with('message', count($validated['ids']).' file(s) deleted successfully.');
    }

    public function createFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
        ]);

        $folder = Folder::create($validated);

        return back()->with('folder', $folder)->with('success', 'Folder created successfully.');
    }

    public function updateFolder(Request $request, Folder $folder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
        ]);

        // Prevent moving folder into itself or its descendants
        if (isset($validated['parent_id'])) {
            if ($validated['parent_id'] == $folder->id) {

                return back()->withErrors(['parent_id' => 'Cannot move folder into itself.']);
            }

            // Check if target is a descendant of this folder
            $targetFolder = Folder::find($validated['parent_id']);
            $current = $targetFolder;
            while ($current) {
                if ($current->parent_id == $folder->id) {
                    return back()->withErrors(['parent_id' => 'Cannot move folder into its own subfolder.']);
                }
                $current = $current->parent;
            }
        }

        $folder->update($validated);
        $folder->fresh();

        return back()->with('folder', $folder)->with('success', 'Folder updated successfully.');
    }

    public function destroyFolder(Folder $folder): RedirectResponse
    {
        if ($folder->files()->exists() || $folder->children()->exists()) {
            return back()->withErrors(['folder' => 'Cannot delete folder with contents.']);
        }

        $folder->delete();

        return back()->with('success', 'Folder deleted successfully.');
    }

    private function generateFileName($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = str($name)->slug()->limit(50, '');
        $timestamp = now()->format('YmdHis');

        return "{$slug}-{$timestamp}.{$extension}";
    }
}
