<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
final class SiteSettingFactory extends Factory
{
    protected $model = SiteSetting::class;

    public function definition(): array
    {
        return [
            'hero_tagline' => 'Portal Terpadu & Ekosistem Digital Multi-Usaha',
            'hero_description' => 'Platform holding terintegrasi untuk pengelolaan unit bisnis, monitoring lisensi aplikasi, dan pelaporan keuangan konsolidasi secara real-time.',
            'hero_image_path' => null,
            'about_short' => 'Holding kami menaungi berbagai unit usaha strategis dengan solusi digital terintegrasi untuk efisiensi dan transparansi operasional.',
            'facebook_url' => 'https://facebook.com/holding',
            'instagram_url' => 'https://instagram.com/holding',
            'youtube_url' => 'https://youtube.com/holding',
            'contact_phone' => '+62 812-3456-7890',
            'contact_email' => 'contact@holding.local',
            'contact_address' => 'Gedung Holding Tower Lt. 12, Jakarta',
            'footer_note' => '© 2026 Holding Portal. Seluruh hak cipta dilindungi.',
        ];
    }
}
