<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Models\Folder;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response|JsonResponse
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

        if ($request->wantsJson()) {
            return response()->json([
                'files' => $files,
                'folders' => $folders,
                'currentFolder' => $currentFolder,
            ]);
        }

        return Inertia::render('admin/media/Index', [
            'files' => $files,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'filters' => $request->only(['folder_id', 'search', 'type']),
        ]);
    }

    public function store(MediaUploadRequest $request): JsonResponse
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

        return response()->json([
            'message' => 'File uploaded successfully.',
            'file' => $media,
        ], 201);
    }

    public function show(Media $media): JsonResponse
    {
        return response()->json([
            'file' => $media->load(['folder', 'uploader:id,name']),
        ]);
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:folders,id'],
        ]);

        $media->update($validated);

        return response()->json([
            'message' => 'File updated successfully.',
            'file' => $media,
        ]);
    }

    public function destroy(Media $media): JsonResponse
    {
        $media->delete();

        return response()->json([
            'message' => 'File deleted successfully.',
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'exists:media,id'],
        ]);

        Media::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => count($validated['ids']).' file(s) deleted successfully.',
        ]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
        ]);

        $folder = Folder::create($validated);

        return response()->json([
            'message' => 'Folder created successfully.',
            'folder' => $folder,
        ], 201);
    }

    public function destroyFolder(Folder $folder): JsonResponse
    {
        if ($folder->files()->exists() || $folder->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete folder with contents.',
            ], 422);
        }

        $folder->delete();

        return response()->json([
            'message' => 'Folder deleted successfully.',
        ]);
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
