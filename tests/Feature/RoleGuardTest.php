<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_owner_cannot_access_admin_routes(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();

        $this->actingAs($user)->get(route('admin.tenants.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.applications.index'))->assertForbidden();
    }

    public function test_tenant_owner_sees_tenant_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantStaff()->for($tenant)->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Dashboard')
                ->where('auth.user.role', 'tenant_staff')
                ->where('tenant.name', $tenant->name));
    }
}
