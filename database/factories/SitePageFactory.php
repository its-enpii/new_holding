<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SitePage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SitePage>
 */
final class SitePageFactory extends Factory
{
    protected $model = SitePage::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'content' => '<p>'.fake()->paragraphs(4, true).'</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'meta_description' => fake()->sentence(8),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
