<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'description' => $this->faker->optional()->sentence(),
            'icon_path' => null,
            'base_url' => $this->faker->unique()->url(),
            'has_financial_report' => true,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withoutFinancialReport(): static
    {
        return $this->state(fn (): array => ['has_financial_report' => false]);
    }
}
