<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'icon',
        'content',
        'status',
        'order',
        'parent_id',
        'is_default',
        'is_expanded',
        'seo_title',
        'seo_description',
        'source',
        'git_path',
        'git_last_commit',
        'git_last_author',
        'updated_at_git',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_default' => 'boolean',
            'is_expanded' => 'boolean',
            'updated_at_git' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Page $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::saved(function (Page $page) {
            $page->clearPathCache();
            // Clear cache for all children as their full paths may have changed
            if ($page->wasChanged(['slug', 'parent_id'])) {
                $page->clearChildrenPathCache();
            }
        });

        static::deleted(function (Page $page) {
            $page->clearPathCache();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderByDesc('version_number');
    }

    public function feedbackResponses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeNavigationTabs($query)
    {
        return $query->where('type', 'navigation')->whereNull('parent_id')->orderBy('order');
    }

    public function scopeGroups($query)
    {
        return $query->where('type', 'group');
    }

    public function scopeDocuments($query)
    {
        return $query->where('type', 'document');
    }

    public function scopeFromGit($query)
    {
        return $query->where('source', 'git');
    }

    public function scopeFromCms($query)
    {
        return $query->where('source', 'cms');
    }

    public function isNavigation(): bool
    {
        return $this->type === 'navigation';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFromGit(): bool
    {
        return $this->source === 'git';
    }

    public function getNavigationTab(): ?Page
    {
        if ($this->isNavigation()) {
            return $this;
        }

        $parent = $this->parent;
        while ($parent) {
            if ($parent->isNavigation()) {
                return $parent;
            }
            $parent = $parent->parent;
        }

        return null;
    }

    public function getFullPath(): string
    {
        return Cache::remember(
            "page.{$this->id}.full_path",
            3600,
            fn () => $this->computeFullPath()
        );
    }

    private function computeFullPath(): string
    {
        $segments = [$this->slug];
        $parentId = $this->parent_id;

        while ($parentId) {
            $parent = Page::select('id', 'slug', 'parent_id')->find($parentId);
            if (! $parent) {
                break;
            }

            array_unshift($segments, $parent->slug);
            $parentId = $parent->parent_id;
        }

        return implode('/', $segments);
    }

    public function clearPathCache(): void
    {
        Cache::forget("page.{$this->id}.full_path");
    }

    public function clearChildrenPathCache(): void
    {
        $this->children()->each(function (Page $child) {
            $child->clearPathCache();
            $child->clearChildrenPathCache();
        });
    }

    public function publish(): self
    {
        $this->update(['status' => 'published']);

        return $this;
    }

    public function unpublish(): self
    {
        $this->update(['status' => 'draft']);

        return $this;
    }

    public function archive(): self
    {
        $this->update(['status' => 'archived']);

        return $this;
    }

    public function createVersion(): PageVersion
    {
        $latestVersion = $this->versions()->max('version_number') ?? 0;

        return $this->versions()->create([
            'content' => $this->content,
            'version_number' => $latestVersion + 1,
            'created_by' => auth()->id(),
        ]);
    }

    public function getEditOnGitHubUrlAttribute(): ?string
    {
        if (! $this->isFromGit() || ! $this->git_path) {
            return null;
        }

        $config = SystemConfig::instance();
        $repoUrl = rtrim($config->git_repository_url ?? '', '/');
        $branch = $config->git_branch ?? 'main';

        return "{$repoUrl}/edit/{$branch}/{$this->git_path}";
    }

    public function getBreadcrumbsAttribute(): array
    {
        $breadcrumbs = collect([['title' => $this->title, 'slug' => $this->slug]]);
        $parent = $this->parent;

        while ($parent) {
            $breadcrumbs->prepend(['title' => $parent->title, 'slug' => $parent->slug]);
            $parent = $parent->parent;
        }

        return $breadcrumbs->toArray();
    }

    public static function buildTree(
        ?\Illuminate\Support\Collection $pages = null,
        ?int $parentId = null,
        array $fields = ['id', 'title', 'slug', 'type', 'parent_id'],
        bool $publishedOnly = false,
        ?callable $transformer = null
    ): array {
        if ($pages === null) {
            $query = static::query()->orderBy('order');

            if ($publishedOnly) {
                $query->published();
            }

            $pages = $query->get($fields);
        }

        return static::buildTreeFromCollection($pages, $parentId, $transformer);
    }

    public static function buildTreeFromCollection(
        \Illuminate\Support\Collection $pages,
        ?int $parentId,
        ?callable $transformer = null
    ): array {
        $items = [];
        $children = $pages->where('parent_id', $parentId);

        foreach ($children as $child) {
            $item = $transformer
                ? $transformer($child, $pages)
                : $child->toArray();

            $childItems = static::buildTreeFromCollection($pages, $child->id, $transformer);

            if (! empty($childItems)) {
                $item['children'] = $childItems;
            }

            $items[] = $item;
        }

        return $items;
    }
}
