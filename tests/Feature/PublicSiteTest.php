<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\SitePage;
use App\Models\SitePost;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_active_applications_and_latest_posts(): void
    {
        SiteSetting::current();

        $app = Application::factory()->create([
            'name' => 'Sistem Logistik',
            'is_active' => true,
        ]);

        $inactiveApp = Application::factory()->create([
            'name' => 'Sistem Lama',
            'is_active' => false,
        ]);

        $post = SitePost::factory()->published()->create([
            'title' => 'Berita Utama Holding',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PublicSite/Landing')
                ->has('applications', 1)
                ->where('applications.0.name', 'Sistem Logistik')
                ->has('latestPosts', 1)
                ->where('latestPosts.0.title', 'Berita Utama Holding')
            );
    }

    public function test_sitemap_xml_returns_valid_xml_with_published_content(): void
    {
        $post = SitePost::factory()->published()->create(['slug' => 'berita-sitemap']);
        $page = SitePage::factory()->create(['slug' => 'halaman-sitemap', 'status' => 'published']);

        $response = $this->get(route('public.sitemap'));
        $response->assertOk();
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('berita-sitemap', $response->getContent());
        $this->assertStringContainsString('halaman-sitemap', $response->getContent());
    }

    public function test_robots_txt_returns_plain_text(): void
    {
        $response = $this->get(route('public.robots'));
        $response->assertOk();
        $this->assertStringContainsString('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Disallow: /admin', $response->getContent());
        $this->assertStringContainsString('Disallow: /website', $response->getContent());
    }
}
