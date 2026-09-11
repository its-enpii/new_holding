<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

final class SitePageRequest extends SiteContentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'slug' => $this->uniqueSlugRule('site_pages', $this->route('page')?->id),
        ];
    }
}
