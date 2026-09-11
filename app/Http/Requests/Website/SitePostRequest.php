<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

final class SitePostRequest extends SiteContentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'slug' => $this->uniqueSlugRule('site_posts', $this->route('post')?->id),
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'excerpt' => 'ringkasan',
            'cover_image' => 'gambar sampul',
        ];
    }
}
