<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TenantApplication>
 */
final class TenantApplicationFactory extends Factory
{
    protected $model = TenantApplication::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'application_id' => Application::factory(),
            'label' => fake()->optional()->words(2, true),
            'instance_url' => fake()->url(),
            'api_secret' => Str::random(40),
            'is_active' => true,
            'activated_at' => now(),
            'expired_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expired_at' => now()->subDay(),
        ]);
    }
}
