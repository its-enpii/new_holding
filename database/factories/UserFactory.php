<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => User::ROLE_TENANT_OWNER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function superadmin(): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => null,
            'role' => User::ROLE_SUPERADMIN,
        ]);
    }

    public function tenantOwner(): static
    {
        return $this->state(fn (): array => ['role' => User::ROLE_TENANT_OWNER]);
    }

    public function tenantStaff(): static
    {
        return $this->state(fn (): array => ['role' => User::ROLE_TENANT_STAFF]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
