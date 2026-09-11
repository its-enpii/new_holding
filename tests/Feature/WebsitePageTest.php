<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SitePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebsitePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_crud_static_pages(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        // Create
        $this->actingAs($superadmin)->post(route('website.pages.store'), [
            'title' => 'Struktur Organisasi',
            'slug' => 'struktur-organisasi',
            'content' => '<p>Bagan dan susunan direksi holding.</p>',
            'status' => 'published',
            'meta_description' => 'Struktur organisasi holding',
        ])->assertRedirect(route('website.pages.index'));

        $page = SitePage::query()->where('slug', 'struktur-organisasi')->firstOrFail();
        $this->assertSame('published', $page->status);

        // Edit
        $this->actingAs($superadmin)->put(route('website.pages.update', $page), [
            'title' => 'Struktur Organisasi 2026',
            'slug' => 'struktur-organisasi',
            'content' => '<p>Bagan terbaru 2026.</p>',
            'status' => 'published',
        ])->assertRedirect(route('website.pages.index'));

        $page->refresh();
        $this->assertSame('Struktur Organisasi 2026', $page->title);

        // Public render /p/struktur-organisasi
        $this->get(route('public.page', 'struktur-organisasi'))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('PublicSite/StaticPage')
                ->where('page.title', 'Struktur Organisasi 2026')
            );

        // Soft delete & restore
        $this->actingAs($superadmin)->delete(route('website.pages.destroy', $page))
            ->assertRedirect(route('website.pages.index'));
        $this->assertSoftDeleted($page);

        $this->get(route('public.page', 'struktur-organisasi'))->assertNotFound();

        $this->actingAs($superadmin)->post(route('website.pages.restore', $page->id))
            ->assertRedirect(route('website.pages.index'));
        $this->assertNotSoftDeleted($page);
    }

    public function test_draft_or_missing_page_returns_404(): void
    {
        SitePage::factory()->draft()->create(['slug' => 'halaman-rahasia']);

        $this->get(route('public.page', 'halaman-rahasia'))->assertNotFound();
        $this->get(route('public.page', 'tidak-ada'))->assertNotFound();
    }
}
