<?php

namespace Tests\Feature\Admin;

use App\Models\Folder;
use App\Models\Media;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();

        SystemConfig::create(['content_mode' => 'cms', 'setup_completed' => true]);
        SystemConfig::clearCache();

        Storage::fake('public');
    }

    public function test_guests_cannot_access_media_index(): void
    {
        $response = $this->get(route('admin.media.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_access_media_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.media.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('admin/media/Index'));
    }

    public function test_media_index_returns_expected_props(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.media.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('files')
            ->has('folders')
            ->has('currentFolder')
            ->has('filters')
            ->has('allFolders')
        );
    }

    public function test_media_index_can_filter_by_folder(): void
    {
        $folder = Folder::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index', ['folder_id' => $folder->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('currentFolder.id', $folder->id)
        );
    }

    public function test_media_index_can_filter_by_search(): void
    {
        $media = $this->createMedia();
        $media->update(['name' => 'unique-searchable-name.jpg']);

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index', ['search' => 'unique-searchable']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('filters.search', 'unique-searchable')
        );
    }

    public function test_media_index_can_filter_by_type(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index', ['type' => 'image']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('filters.type', 'image')
        );
    }

    public function test_users_can_upload_media(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 100, 100);

        $response = $this->actingAs($this->user)
            ->post(route('admin.media.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File uploaded successfully.');

        $this->assertDatabaseHas('media', [
            'model_type' => User::class,
            'model_id' => $this->user->id,
        ]);
    }

    public function test_users_can_upload_media_to_folder(): void
    {
        $folder = Folder::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 100, 100);

        $response = $this->actingAs($this->user)
            ->post(route('admin.media.store'), [
                'file' => $file,
                'folder_id' => $folder->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('media', [
            'folder_id' => $folder->id,
        ]);
    }

    public function test_upload_requires_file(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.media.store'), []);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('malicious.exe', 100);

        $response = $this->actingAs($this->user)
            ->post(route('admin.media.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_users_can_view_media_details(): void
    {
        $media = $this->createMedia();

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.show', $media));

        $response->assertRedirect();
        $response->assertSessionHas('file');
    }

    public function test_users_can_update_media_name(): void
    {
        $media = $this->createMedia();

        $response = $this->actingAs($this->user)
            ->patch(route('admin.media.update', $media), [
                'name' => 'updated-name.jpg',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File updated successfully.');

        $media->refresh();
        $this->assertEquals('updated-name.jpg', $media->name);
    }

    public function test_users_can_move_media_to_folder(): void
    {
        $media = $this->createMedia();
        $folder = Folder::factory()->create();

        $response = $this->actingAs($this->user)
            ->patch(route('admin.media.update', $media), [
                'folder_id' => $folder->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $media->refresh();
        $this->assertEquals($folder->id, $media->folder_id);
    }

    public function test_users_can_move_media_to_root(): void
    {
        $folder = Folder::factory()->create();
        $media = $this->createMedia(['folder_id' => $folder->id]);

        $response = $this->actingAs($this->user)
            ->patch(route('admin.media.update', $media), [
                'folder_id' => null,
            ]);

        $response->assertRedirect();

        $media->refresh();
        $this->assertNull($media->folder_id);
    }

    public function test_users_can_delete_media(): void
    {
        $media = $this->createMedia();
        $mediaId = $media->id;

        $response = $this->actingAs($this->user)
            ->delete(route('admin.media.destroy', $media));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File deleted successfully.');

        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
    }

    public function test_users_can_bulk_delete_media(): void
    {
        $media1 = $this->createMedia();
        $media2 = $this->createMedia();

        $response = $this->actingAs($this->user)
            ->post(route('admin.media.bulk-destroy'), [
                'ids' => [$media1->id, $media2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('message', '2 file(s) deleted successfully.');

        $this->assertDatabaseMissing('media', ['id' => $media1->id]);
        $this->assertDatabaseMissing('media', ['id' => $media2->id]);
    }

    public function test_bulk_delete_requires_ids(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.media.bulk-destroy'), []);

        $response->assertSessionHasErrors('ids');
    }

    public function test_users_can_create_folder(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.media.folders.store'), [
                'name' => 'New Folder',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Folder created successfully.');

        $this->assertDatabaseHas('folders', ['name' => 'New Folder']);
    }

    public function test_users_can_create_nested_folder(): void
    {
        $parentFolder = Folder::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('admin.media.folders.store'), [
                'name' => 'Child Folder',
                'parent_id' => $parentFolder->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('folders', [
            'name' => 'Child Folder',
            'parent_id' => $parentFolder->id,
        ]);
    }

    public function test_folder_creation_requires_name(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.media.folders.store'), []);

        $response->assertSessionHasErrors('name');
    }

    public function test_users_can_update_folder_name(): void
    {
        $folder = Folder::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($this->user)
            ->patch(route('admin.media.folders.update', $folder), [
                'name' => 'Updated Name',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Folder updated successfully.');

        $folder->refresh();
        $this->assertEquals('Updated Name', $folder->name);
    }

    public function test_users_can_move_folder_to_parent(): void
    {
        $parentFolder = Folder::factory()->create();
        $childFolder = Folder::factory()->create();

        $response = $this->actingAs($this->user)
            ->patch(route('admin.media.folders.update', $childFolder), [
                'parent_id' => $parentFolder->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $childFolder->refresh();
        $this->assertEquals($parentFolder->id, $childFolder->parent_id);
    }

    public function test_cannot_move_folder_into_itself(): void
    {
        $folder = Folder::factory()->create();

        $response = $this->actingAs($this->user)
            ->patch(route('admin.media.folders.update', $folder), [
                'parent_id' => $folder->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('parent_id');

        $folder->refresh();
        $this->assertNull($folder->parent_id);
    }

    public function test_cannot_move_folder_into_own_subfolder(): void
    {
        $parentFolder = Folder::factory()->create();
        $childFolder = Folder::factory()->create(['parent_id' => $parentFolder->id]);

        $response = $this->actingAs($this->user)
            ->patch(route('admin.media.folders.update', $parentFolder), [
                'parent_id' => $childFolder->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('parent_id');
    }

    public function test_users_can_delete_empty_folder(): void
    {
        $folder = Folder::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('admin.media.folders.destroy', $folder));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Folder deleted successfully.');

        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    }

    public function test_cannot_delete_folder_with_files(): void
    {
        $folder = Folder::factory()->create();
        $this->createMedia(['folder_id' => $folder->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.media.folders.destroy', $folder));

        $response->assertRedirect();
        $response->assertSessionHasErrors('folder');

        $this->assertDatabaseHas('folders', ['id' => $folder->id]);
    }

    public function test_cannot_delete_folder_with_subfolders(): void
    {
        $parentFolder = Folder::factory()->create();
        Folder::factory()->create(['parent_id' => $parentFolder->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.media.folders.destroy', $parentFolder));

        $response->assertRedirect();
        $response->assertSessionHasErrors('folder');

        $this->assertDatabaseHas('folders', ['id' => $parentFolder->id]);
    }

    public function test_media_shows_in_correct_folder(): void
    {
        $folder = Folder::factory()->create();
        $mediaInFolder = $this->createMedia(['folder_id' => $folder->id]);
        $mediaInRoot = $this->createMedia();

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index', ['folder_id' => $folder->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('currentFolder.id', $folder->id)
            ->has('files.data', 1)
        );

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('currentFolder', null)
            ->has('files.data', 1)
        );
    }

    public function test_folders_show_in_correct_parent(): void
    {
        $parentFolder = Folder::factory()->create();
        $childFolder = Folder::factory()->create(['parent_id' => $parentFolder->id]);
        $rootFolder = Folder::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('folders', 2)
        );

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index', ['folder_id' => $parentFolder->id]));

        $response->assertInertia(fn ($page) => $page
            ->has('folders', 1)
        );
    }

    private function createMedia(array $attributes = []): Media
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $media = $this->user->addMedia($file)
            ->usingFileName('test-'.uniqid().'.jpg')
            ->toMediaCollection('uploads');

        if (! empty($attributes)) {
            $media->update($attributes);
            $media->refresh();
        }

        return $media;
    }
}
