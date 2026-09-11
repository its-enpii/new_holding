<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Website;

use App\Http\Requests\Website\SitePostRequest;
use App\Models\SitePost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class WebsitePostController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = $this->perPage($request->query('per_page'));
        $sort = $this->sort((string) $request->query('sort', 'published_at'));
        $direction = $this->direction((string) $request->query('direction', 'desc'));

        $posts = SitePost::query()
            ->withTrashed()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SitePost $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'published_at' => $post->published_at?->toIso8601String(),
                'cover_image_path' => $post->cover_image_path,
                'cover_image_url' => $post->cover_image_path !== null ? Storage::disk('public')->url($post->cover_image_path) : null,
                'deleted_at' => $post->deleted_at?->toIso8601String(),
            ]);

        return Inertia::render('Website/Posts/Index', [
            'posts' => $posts,
            'search' => $search,
            'perPage' => $perPage,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Website/Posts/Form', ['post' => null]);
    }

    public function store(SitePostRequest $request): RedirectResponse
    {
        $attributes = $this->persistedAttributes($request, null);

        $post = SitePost::query()->create([
            ...$attributes,
            'author_name' => $request->user()?->name,
        ]);

        $this->storeCoverImage($request, $post);

        return to_route('website.posts.index')->with('success', 'Berita berhasil ditambahkan.');
    }

    public function edit(SitePost $post): Response
    {
        return Inertia::render('Website/Posts/Form', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'status' => $post->status,
                'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
                'meta_description' => $post->meta_description,
                'cover_image_path' => $post->cover_image_path,
                'cover_image_url' => $post->cover_image_path !== null ? Storage::disk('public')->url($post->cover_image_path) : null,
            ],
        ]);
    }

    public function update(SitePostRequest $request, SitePost $post): RedirectResponse
    {
        $post->update($this->persistedAttributes($request, $post));

        $this->storeCoverImage($request, $post);

        return to_route('website.posts.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(SitePost $post): RedirectResponse
    {
        $post->delete();

        return to_route('website.posts.index')->with('success', 'Berita dipindahkan ke sampah.');
    }

    public function restore(int $postId): RedirectResponse
    {
        $post = SitePost::withTrashed()->findOrFail($postId);
        $post->restore();

        return to_route('website.posts.index')->with('success', 'Berita dipulihkan.');
    }

    public function removeCover(SitePost $post): RedirectResponse
    {
        if (is_string($post->cover_image_path) && $post->cover_image_path !== '') {
            Storage::disk('public')->delete($post->cover_image_path);
            $post->update(['cover_image_path' => null]);
        }

        return back()->with('success', 'Gambar sampul dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function persistedAttributes(SitePostRequest $request, ?SitePost $post): array
    {
        $validated = $request->validated();

        $slug = (string) ($validated['slug'] ?? '');
        if ($slug === '') {
            $slug = $post?->slug ?? $this->uniqueSlug($this->slugify((string) $validated['title']), $post?->id);
        }

        $publishedAt = $validated['published_at'] ?? null;
        if ($validated['status'] === 'published') {
            $publishedAt ??= $post?->published_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        } else {
            $publishedAt = null;
        }

        return [
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'meta_description' => $validated['meta_description'] ?? null,
        ];
    }

    private function storeCoverImage(SitePostRequest $request, SitePost $post): void
    {
        if ($request->hasFile('cover_image')) {
            $oldPath = $post->cover_image_path;
            $path = $request->file('cover_image')->store('site/posts', 'public');

            if (is_string($oldPath) && $oldPath !== '') {
                Storage::disk('public')->delete($oldPath);
            }

            $post->update(['cover_image_path' => $path]);
        }
    }

    private function slugify(string $title): string
    {
        $ascii = Str::ascii(Str::transliterate($title));

        return trim(Str::lower(preg_replace('/[^A-Za-z0-9]+/', '-', $ascii) ?? ''), '-') ?: 'berita';
    }

    private function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $slug = $base;
        $suffix = 2;

        while (SitePost::query()
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
        ][$value] ?? 'published_at';
    }

    private function direction(string $value): string
    {
        return in_array($value, ['asc', 'desc'], true) ? $value : 'desc';
    }
}
