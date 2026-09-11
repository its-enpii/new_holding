<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SitePostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SitePost extends Model
{
    /** @use HasFactory<SitePostFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'site_posts';

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'cover_image_path',
        'status',
        'published_at',
        'author_name',
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
        return $this->getAttribute('status') === 'published'
            && $this->getAttribute('published_at') !== null
            && $this->getAttribute('published_at')->lte(now());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
