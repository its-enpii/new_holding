<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SitePageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SitePage extends Model
{
    /** @use HasFactory<SitePageFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'site_pages';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'status',
        'published_at',
        'meta_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function isPublished(): bool
    {
        return $this->getAttribute('status') === 'published';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
