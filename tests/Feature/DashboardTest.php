<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_statistics(): void
    {
        $user = User::factory()->superadmin()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('auth.user.role', 'superadmin')
                ->where('stats.activeTenants', 0)
                ->where('stats.applications', 0)
                ->where('stats.users', 1));
    }
}
