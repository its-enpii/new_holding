<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SitePost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SitePost>
 */
final class SitePostFactory extends Factory
{
    protected $model = SitePost::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'excerpt' => fake()->paragraph(),
            'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'cover_image_path' => null,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'author_name' => fake()->name(),
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

    public function published(): self
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);
    }
}
