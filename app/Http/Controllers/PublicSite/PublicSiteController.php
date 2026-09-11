<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Http\Requests\PublicSite\SiteMessageRequest;
use App\Models\Application;
use App\Models\SiteMessage;
use App\Models\SitePage;
use App\Models\SitePost;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class PublicSiteController
{
    /**
     * Public Landing Page for the Holding Portal.
     */
    public function home(): Response
    {
        $settings = $this->resolveSettings();

        $applications = Application::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'icon_path', 'base_url', 'has_financial_report'])
            ->map(fn (Application $app): array => [
                'id' => $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
                'description' => $app->description,
                'icon_path' => $app->icon_path,
                'base_url' => $app->base_url,
                'has_financial_report' => $app->has_financial_report,
            ]);

        $latestPosts = SitePost::query()
            ->published()
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->map(fn (SitePost $post): array => [
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'cover_image_url' => $post->cover_image_path !== null ? Storage::disk('public')->url($post->cover_image_path) : null,
                'published_at' => $post->published_at?->toIso8601String(),
            ]);

        return Inertia::render('PublicSite/Landing', [
            'settings' => $settings,
            'applications' => $applications,
            'latestPosts' => $latestPosts,
        ]);
    }

    /**
     * Public blog index.
     */
    public function posts(Request $request): Response
    {
        $settings = $this->resolveSettings();
        $search = trim((string) $request->query('q', ''));

        $posts = SitePost::query()
            ->published()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (SitePost $post): array => [
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'cover_image_url' => $post->cover_image_path !== null ? Storage::disk('public')->url($post->cover_image_path) : null,
                'published_at' => $post->published_at?->toIso8601String(),
                'author_name' => $post->author_name,
            ]);

        return Inertia::render('PublicSite/BlogIndex', [
            'settings' => $settings,
            'posts' => $posts,
            'search' => $search,
        ]);
    }

    /**
     * Public blog post detail.
     */
    public function post(string $slug): Response
    {
        $settings = $this->resolveSettings();

        $post = SitePost::query()->published()->where('slug', $slug)->first();

        if ($post === null) {
            abort(404);
        }

        $relatedPosts = SitePost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->map(fn (SitePost $p): array => [
                'slug' => $p->slug,
                'title' => $p->title,
                'excerpt' => $p->excerpt,
                'cover_image_url' => $p->cover_image_path !== null ? Storage::disk('public')->url($p->cover_image_path) : null,
                'published_at' => $p->published_at?->toIso8601String(),
            ]);

        return Inertia::render('PublicSite/BlogPost', [
            'settings' => $settings,
            'post' => [
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'cover_image_url' => $post->cover_image_path !== null ? Storage::disk('public')->url($post->cover_image_path) : null,
                'published_at' => $post->published_at?->toIso8601String(),
                'author_name' => $post->author_name,
                'meta_description' => $post->meta_description,
            ],
            'relatedPosts' => $relatedPosts,
        ]);
    }

    /**
     * Public static page detail.
     */
    public function page(string $slug): Response
    {
        $settings = $this->resolveSettings();

        $page = SitePage::query()->published()->where('slug', $slug)->first();

        if ($page === null) {
            abort(404);
        }

        return Inertia::render('PublicSite/StaticPage', [
            'settings' => $settings,
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
                'published_at' => $page->published_at?->toIso8601String(),
                'meta_description' => $page->meta_description,
            ],
        ]);
    }

    /**
     * Public contact page.
     */
    public function contact(): Response
    {
        return Inertia::render('PublicSite/Contact', [
            'settings' => $this->resolveSettings(),
        ]);
    }

    /**
     * Handle contact form submission.
     */
    public function storeMessage(SiteMessageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Honeypot check: if filled, pretend success without writing to DB
        if (! empty($validated['website'])) {
            return redirect()->back()->with('success', 'Pesan Anda berhasil terkirim.');
        }

        SiteMessage::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
        ]);

        return redirect()->back()->with('success', 'Pesan Anda berhasil dikirim. Kami akan segera menghubungi Anda.');
    }

    /**
     * XML Sitemap.
     */
    public function sitemap(): SymfonyResponse
    {
        $urls = [
            route('home'),
            route('public.posts'),
            route('public.contact'),
        ];

        foreach (SitePost::query()->published()->orderByDesc('published_at')->get(['slug']) as $post) {
            $urls[] = route('public.post', $post->slug);
        }

        foreach (SitePage::query()->published()->orderBy('slug')->get(['slug']) as $page) {
            $urls[] = route('public.page', $page->slug);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8').'</loc></url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * robots.txt.
     */
    public function robots(): SymfonyResponse
    {
        $lines = [
            'User-agent: *',
            'Disallow: /login',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /tenant',
            'Disallow: /website',
            '',
            'Sitemap: '.route('public.sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveSettings(): array
    {
        $settings = SiteSetting::current();

        return [
            'hero_tagline' => $settings->hero_tagline,
            'hero_description' => $settings->hero_description,
            'hero_image_url' => $settings->hero_image_path
                ? Storage::disk('public')->url($settings->hero_image_path)
                : null,
            'about_short' => $settings->about_short,
            'social' => [
                'facebook' => $settings->facebook_url,
                'instagram' => $settings->instagram_url,
                'youtube' => $settings->youtube_url,
            ],
            'contact_phone' => $settings->contact_phone,
            'contact_email' => $settings->contact_email,
            'contact_address' => $settings->contact_address,
            'footer_note' => $settings->footer_note,
        ];
    }
}
