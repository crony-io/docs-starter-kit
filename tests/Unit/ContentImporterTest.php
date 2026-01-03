<?php

namespace Tests\Unit;

use App\Models\Page;
use App\Services\ContentImporter;
use App\Services\PageImporterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentImporterTest extends TestCase
{
    use RefreshDatabase;

    private ContentImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new ContentImporter(new PageImporterService);
    }

    public function test_import_creates_page_from_parsed_content()
    {
        $parsedContent = [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Test content',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
            'order' => 1,
            'status' => 'published',
            'hierarchy' => ['docs', 'test-page'],
            'git_path' => 'docs/test-page.md',
        ];

        $commitInfo = [
            'sha' => 'abc123',
            'author' => 'Test Author',
            'date' => now(),
        ];

        $page = $this->importer->import($parsedContent, $commitInfo);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('Test Page', $page->title);
        $this->assertEquals('test-page', $page->slug);
        $this->assertEquals('document', $page->type);
        $this->assertEquals('git', $page->source);
        $this->assertEquals('abc123', $page->git_last_commit);
    }

    public function test_import_creates_navigation_page_for_top_level_hierarchy()
    {
        $parsedContent = [
            'title' => 'Getting Started',
            'slug' => 'installation',
            'content' => 'Content',
            'seo_title' => null,
            'seo_description' => null,
            'order' => 0,
            'status' => 'published',
            'hierarchy' => ['getting-started', 'installation'],
            'git_path' => 'docs/getting-started/installation.md',
        ];

        $commitInfo = [
            'sha' => 'abc123',
            'author' => 'Author',
            'date' => now(),
        ];

        $page = $this->importer->import($parsedContent, $commitInfo);

        $navigation = Page::where('slug', 'getting-started')
            ->where('type', 'navigation')
            ->first();

        $this->assertNotNull($navigation);
        $this->assertEquals($navigation->id, $page->parent_id);
    }

    public function test_import_creates_group_pages_for_nested_hierarchy()
    {
        $parsedContent = [
            'title' => 'Deep Page',
            'slug' => 'deep',
            'content' => 'Content',
            'seo_title' => null,
            'seo_description' => null,
            'order' => 0,
            'status' => 'published',
            'hierarchy' => ['docs', 'section', 'subsection', 'deep'],
            'git_path' => 'docs/docs/section/subsection/deep.md',
        ];

        $commitInfo = [
            'sha' => 'abc123',
            'author' => 'Author',
            'date' => now(),
        ];

        $page = $this->importer->import($parsedContent, $commitInfo);

        $this->assertDatabaseHas('pages', [
            'slug' => 'docs',
            'type' => 'navigation',
            'source' => 'git',
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'section',
            'type' => 'group',
            'source' => 'git',
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'subsection',
            'type' => 'group',
            'source' => 'git',
        ]);
    }

    public function test_import_updates_existing_page_by_git_path()
    {
        $existingPage = Page::factory()->fromGit()->create([
            'git_path' => 'docs/test.md',
            'title' => 'Old Title',
        ]);

        $parsedContent = [
            'title' => 'New Title',
            'slug' => 'test',
            'content' => 'Updated content',
            'seo_title' => null,
            'seo_description' => null,
            'order' => 0,
            'status' => 'published',
            'hierarchy' => ['test'],
            'git_path' => 'docs/test.md',
        ];

        $commitInfo = [
            'sha' => 'newsha456',
            'author' => 'New Author',
            'date' => now(),
        ];

        $page = $this->importer->import($parsedContent, $commitInfo);

        $this->assertEquals($existingPage->id, $page->id);
        $this->assertEquals('New Title', $page->title);
        $this->assertEquals('newsha456', $page->git_last_commit);
    }

    public function test_import_does_not_create_parent_for_root_level_document()
    {
        $parsedContent = [
            'title' => 'Root Page',
            'slug' => 'root',
            'content' => 'Content',
            'seo_title' => null,
            'seo_description' => null,
            'order' => 0,
            'status' => 'published',
            'hierarchy' => [],
            'git_path' => 'docs/root.md',
        ];

        $commitInfo = [
            'sha' => 'abc123',
            'author' => 'Author',
            'date' => now(),
        ];

        $page = $this->importer->import($parsedContent, $commitInfo);

        $this->assertNull($page->parent_id);
    }

    public function test_delete_removed_pages_removes_documents_not_in_paths()
    {
        $keepPage = Page::factory()->fromGit()->create([
            'git_path' => 'docs/keep.md',
            'type' => 'document',
        ]);

        $deletePage = Page::factory()->fromGit()->create([
            'git_path' => 'docs/delete.md',
            'type' => 'document',
        ]);

        $result = $this->importer->deleteRemovedPages(['docs/keep.md']);

        $this->assertDatabaseHas('pages', ['id' => $keepPage->id]);
        $this->assertDatabaseMissing('pages', ['id' => $deletePage->id]);
        $this->assertEquals(1, $result['documents']);
    }

    public function test_delete_removed_pages_removes_empty_groups()
    {
        $group = Page::factory()->fromGit()->create([
            'type' => 'group',
            'git_path' => 'docs/empty-group',
        ]);

        $result = $this->importer->deleteRemovedPages([]);

        $this->assertDatabaseMissing('pages', ['id' => $group->id]);
        $this->assertGreaterThanOrEqual(1, $result['groups']);
    }

    public function test_delete_removed_pages_keeps_groups_with_children()
    {
        $group = Page::factory()->fromGit()->create([
            'type' => 'group',
            'git_path' => 'docs/parent-group',
        ]);

        $child = Page::factory()->fromGit()->child($group)->create([
            'type' => 'document',
            'git_path' => 'docs/parent-group/child.md',
        ]);

        $result = $this->importer->deleteRemovedPages(['docs/parent-group/child.md']);

        $this->assertDatabaseHas('pages', ['id' => $group->id]);
        $this->assertDatabaseHas('pages', ['id' => $child->id]);
    }

    public function test_delete_removed_pages_does_not_affect_cms_pages()
    {
        $cmsPage = Page::factory()->create([
            'source' => 'cms',
            'type' => 'document',
        ]);

        $result = $this->importer->deleteRemovedPages([]);

        $this->assertDatabaseHas('pages', ['id' => $cmsPage->id]);
    }
}
