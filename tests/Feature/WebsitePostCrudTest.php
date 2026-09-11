<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SitePost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WebsitePostCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_draft_and_publish_post_and_view_on_public_blog(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        // 1. Create draft post
        $this->actingAs($superadmin)->post(route('website.posts.store'), [
            'title' => 'Pengumuman Rapat Tahunan',
            'slug' => 'pengumuman-rapat-tahunan',
            'excerpt' => 'Ringkasan rapat tahunan holding',
            'content' => '<p>Isi lengkap pengumuman rapat tahunan holding.</p>',
            'status' => 'draft',
            'meta_description' => 'Meta deskripsi rapat tahunan',
        ])->assertRedirect(route('website.posts.index'));

        $post = SitePost::query()->where('slug', 'pengumuman-rapat-tahunan')->firstOrFail();
        $this->assertSame('draft', $post->status);
        $this->assertNull($post->published_at);

        // Draft should not appear on public blog index or slug detail
        $this->get(route('public.posts'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('PublicSite/BlogIndex')->where('posts.data', []));

        $this->get(route('public.post', 'pengumuman-rapat-tahunan'))->assertNotFound();

        // 2. Publish the post
        $this->actingAs($superadmin)->put(route('website.posts.update', $post), [
            'title' => 'Pengumuman Rapat Tahunan Revisi',
            'slug' => 'pengumuman-rapat-tahunan',
            'excerpt' => 'Ringkasan rapat tahunan holding yang telah direvisi',
            'content' => '<p>Isi lengkap pengumuman rapat tahunan holding.</p>',
            'status' => 'published',
            'published_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'meta_description' => 'Meta deskripsi rapat tahunan',
        ])->assertRedirect(route('website.posts.index'));

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);

        // Published post appears on public blog index
        $this->get(route('public.posts'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PublicSite/BlogIndex')
                ->has('posts.data', 1)
                ->where('posts.data.0.slug', 'pengumuman-rapat-tahunan')
                ->where('posts.data.0.title', 'Pengumuman Rapat Tahunan Revisi')
            );

        // Published post slug detail renders
        $this->get(route('public.post', 'pengumuman-rapat-tahunan'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PublicSite/BlogPost')
                ->where('post.slug', 'pengumuman-rapat-tahunan')
                ->where('post.title', 'Pengumuman Rapat Tahunan Revisi')
            );
    }

    public function test_superadmin_can_upload_and_remove_cover_image(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->superadmin()->create();

        $file = UploadedFile::fake()->image('cover.jpg', 600, 400);

        $this->actingAs($superadmin)->post(route('website.posts.store'), [
            'title' => 'Berita Dengan Cover',
            'content' => '<p>Konten berita</p>',
            'status' => 'published',
            'cover_image' => $file,
        ])->assertRedirect(route('website.posts.index'));

        $post = SitePost::query()->where('title', 'Berita Dengan Cover')->firstOrFail();
        $this->assertNotNull($post->cover_image_path);
        Storage::disk('public')->assertExists($post->cover_image_path);

        // Remove cover
        $this->actingAs($superadmin)->delete(route('website.posts.remove-cover', $post))
            ->assertRedirect();

        $post->refresh();
        $this->assertNull($post->cover_image_path);
    }

    public function test_superadmin_can_soft_delete_and_restore_post(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $post = SitePost::factory()->published()->create();

        // Soft delete
        $this->actingAs($superadmin)->delete(route('website.posts.destroy', $post))
            ->assertRedirect(route('website.posts.index'));

        $this->assertSoftDeleted($post);

        // Deleted post should not appear publicly
        $this->get(route('public.post', $post->slug))->assertNotFound();

        // Restore
        $this->actingAs($superadmin)->post(route('website.posts.restore', $post->id))
            ->assertRedirect(route('website.posts.index'));

        $this->assertNotSoftDeleted($post);
        $this->get(route('public.post', $post->slug))->assertOk();
    }
}
