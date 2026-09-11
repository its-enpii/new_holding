<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
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

    public function test_superadmin_can_view_dashboard_with_expiring_license(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create();

        TenantApplication::factory()->create([
            'tenant_id' => $tenant->id,
            'application_id' => $application->id,
            'expired_at' => now()->addDays(3),
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('licenseAlerts.items', 1)
                ->where('licenseAlerts.items.0.status', 'expiring'));
    }
}
