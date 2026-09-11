<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    protected $table = 'site_settings';

    protected $fillable = [
        'hero_tagline',
        'hero_description',
        'hero_image_path',
        'about_short',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'contact_phone',
        'contact_email',
        'contact_address',
        'footer_note',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'hero_tagline' => 'Portal Terpadu & Ekosistem Digital Multi-Usaha',
            'hero_description' => 'Platform holding terintegrasi untuk pengelolaan unit bisnis, monitoring lisensi aplikasi, dan pelaporan keuangan konsolidasi secara real-time.',
            'about_short' => 'Holding kami menaungi berbagai unit usaha strategis dengan solusi digital terintegrasi untuk efisiensi dan transparansi operasional.',
            'contact_email' => 'contact@holding.local',
            'contact_phone' => '+62 812-3456-7890',
            'contact_address' => 'Gedung Holding Tower Lt. 12, Jakarta',
            'footer_note' => '© '.date('Y').' Holding Portal. Seluruh hak cipta dilindungi.',
        ]);
    }
}
