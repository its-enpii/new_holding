<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SiteMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteMessage>
 */
final class SiteMessageFactory extends Factory
{
    protected $model = SiteMessage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'subject' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'read_at' => null,
        ];
    }

    public function read(): self
    {
        return $this->state(fn (): array => [
            'read_at' => now()->subDay(),
        ]);
    }
}
