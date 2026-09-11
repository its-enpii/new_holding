<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Website;

use App\Http\Requests\Website\SitePageRequest;
use App\Models\SitePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class WebsitePageController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = $this->perPage($request->query('per_page'));
        $sort = $this->sort((string) $request->query('sort', 'title'));
        $direction = $this->direction((string) $request->query('direction', 'asc'));

        $pages = SitePage::query()
            ->withTrashed()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SitePage $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'published_at' => $page->published_at?->toIso8601String(),
                'deleted_at' => $page->deleted_at?->toIso8601String(),
            ]);

        return Inertia::render('Website/Pages/Index', [
            'pages' => $pages,
            'search' => $search,
            'perPage' => $perPage,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Website/Pages/Form', ['page' => null]);
    }

    public function store(SitePageRequest $request): RedirectResponse
    {
        $attributes = $this->persistedAttributes($request, null);

        SitePage::query()->create($attributes);

        return to_route('website.pages.index')->with('success', 'Halaman berhasil ditambahkan.');
    }

    public function edit(SitePage $page): Response
    {
        return Inertia::render('Website/Pages/Form', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'status' => $page->status,
                'published_at' => $page->published_at?->format('Y-m-d\TH:i'),
                'meta_description' => $page->meta_description,
            ],
        ]);
    }

    public function update(SitePageRequest $request, SitePage $page): RedirectResponse
    {
        $page->update($this->persistedAttributes($request, $page));

        return to_route('website.pages.index')->with('success', 'Halaman berhasil diperbarui.');
    }

    public function destroy(SitePage $page): RedirectResponse
    {
        $page->delete();

        return to_route('website.pages.index')->with('success', 'Halaman dipindahkan ke sampah.');
    }

    public function restore(int $pageId): RedirectResponse
    {
        $page = SitePage::withTrashed()->findOrFail($pageId);
        $page->restore();

        return to_route('website.pages.index')->with('success', 'Halaman dipulihkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function persistedAttributes(SitePageRequest $request, ?SitePage $page): array
    {
        $validated = $request->validated();

        $slug = (string) ($validated['slug'] ?? '');
        if ($slug === '') {
            $slug = $page?->slug ?? $this->uniqueSlug($this->slugify((string) $validated['title']), $page?->id);
        }

        $publishedAt = $validated['published_at'] ?? null;
        if ($validated['status'] === 'published') {
            $publishedAt ??= $page?->published_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        } else {
            $publishedAt = null;
        }

        return [
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'meta_description' => $validated['meta_description'] ?? null,
        ];
    }

    private function slugify(string $title): string
    {
        $ascii = Str::ascii(Str::transliterate($title));

        return trim(Str::lower(preg_replace('/[^A-Za-z0-9]+/', '-', $ascii) ?? ''), '-') ?: 'halaman';
    }

    private function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $slug = $base;
        $suffix = 2;

        while (SitePage::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function perPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, [15, 30, 50, 100], true) ? $value : 15;
    }

    private function sort(string $value): string
    {
        return [
            'title' => 'title',
            'status' => 'status',
            'published_at' => 'published_at',
        ][$value] ?? 'title';
    }

    private function direction(string $value): string
    {
        return in_array($value, ['asc', 'desc'], true) ? $value : 'asc';
    }
}
