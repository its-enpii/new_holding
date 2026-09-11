<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Website;

use App\Http\Requests\Website\SiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class WebsiteSettingController
{
    public function edit(): Response
    {
        $settings = SiteSetting::current();

        return Inertia::render('Website/Settings/Form', [
            'settings' => [
                'hero_tagline' => $settings->hero_tagline,
                'hero_description' => $settings->hero_description,
                'about_short' => $settings->about_short,
                'facebook_url' => $settings->facebook_url,
                'instagram_url' => $settings->instagram_url,
                'youtube_url' => $settings->youtube_url,
                'contact_phone' => $settings->contact_phone,
                'contact_email' => $settings->contact_email,
                'contact_address' => $settings->contact_address,
                'footer_note' => $settings->footer_note,
            ],
            'heroImageUrl' => $settings->hero_image_path
                ? Storage::disk('public')->url($settings->hero_image_path)
                : null,
        ]);
    }

    public function update(SiteSettingRequest $request): RedirectResponse
    {
        $settings = SiteSetting::current();

        $attributes = collect($request->validated())->except(['hero_image', 'remove_hero_image'])->all();

        if ($request->boolean('remove_hero_image')) {
            if ($settings->hero_image_path) {
                Storage::disk('public')->delete($settings->hero_image_path);
            }

            $attributes['hero_image_path'] = null;
        }

        if ($request->hasFile('hero_image')) {
            $oldPath = $settings->hero_image_path;
            $path = $request->file('hero_image')->store('site/settings', 'public');

            if (is_string($oldPath) && $oldPath !== '') {
                Storage::disk('public')->delete($oldPath);
            }

            $attributes['hero_image_path'] = $path;
        }

        $settings->update($attributes);

        return to_route('website.settings.edit')->with('success', 'Pengaturan situs berhasil disimpan.');
    }
}
