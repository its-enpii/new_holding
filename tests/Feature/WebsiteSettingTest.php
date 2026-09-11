<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WebsiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_settings_and_renders_on_landing_page(): void
    {
        Storage::fake('public');
        $superadmin = User::factory()->superadmin()->create();

        $heroImage = UploadedFile::fake()->image('hero.png', 1200, 600);

        $this->actingAs($superadmin)->put(route('website.settings.update'), [
            'hero_tagline' => 'Transformasi Digital Holding 2026',
            'hero_description' => 'Membangun ekosistem bisnis terintegrasi dan berdaya saing tinggi.',
            'about_short' => 'Holding kami menaungi puluhan unit bisnis di berbagai sektor strategis.',
            'facebook_url' => 'https://facebook.com/holdingportal',
            'instagram_url' => 'https://instagram.com/holdingportal',
            'youtube_url' => 'https://youtube.com/holdingportal',
            'contact_phone' => '+62 811-9999-8888',
            'contact_email' => 'info@holdingportal.id',
            'contact_address' => 'Gedung Holding Baru Lantai 20, Jakarta',
            'footer_note' => '© 2026 PT Holding Multi Usaha.',
            'hero_image' => $heroImage,
        ])->assertRedirect(route('website.settings.edit'));

        $settings = SiteSetting::current();
        $this->assertSame('Transformasi Digital Holding 2026', $settings->hero_tagline);
        $this->assertNotNull($settings->hero_image_path);
        Storage::disk('public')->assertExists($settings->hero_image_path);

        // Landing page should render updated settings
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PublicSite/Landing')
                ->where('settings.hero_tagline', 'Transformasi Digital Holding 2026')
                ->where('settings.about_short', 'Holding kami menaungi puluhan unit bisnis di berbagai sektor strategis.')
                ->where('settings.contact_email', 'info@holdingportal.id')
            );
    }
}
