<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

final class SiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hero_tagline' => ['nullable', 'string', 'max:200'],
            'hero_description' => ['nullable', 'string', 'max:500'],
            'hero_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_hero_image' => ['nullable', 'boolean'],
            'about_short' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'footer_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hero_tagline' => 'tagline hero',
            'hero_description' => 'deskripsi hero',
            'hero_image' => 'gambar hero',
            'about_short' => 'tentang singkat',
            'facebook_url' => 'tautan Facebook',
            'instagram_url' => 'tautan Instagram',
            'youtube_url' => 'tautan YouTube',
            'contact_phone' => 'telepon',
            'contact_email' => 'email kontak',
            'contact_address' => 'alamat kontak',
            'footer_note' => 'catatan footer',
        ];
    }
}
