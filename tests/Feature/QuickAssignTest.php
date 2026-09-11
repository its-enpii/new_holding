<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class QuickAssignTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_quick_assigns_an_application_to_a_tenant(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create(['name' => 'BUMDesma Maju']);
        $application = Application::factory()->create(['name' => 'SIDBM']);

        $response = $this->actingAs($superadmin)
            ->from(route('dashboard'))
            ->post(route('admin.tenants.applications.quick-assign', $tenant), [
                'application_id' => $application->id,
            ]);

        $tenantApplication = $tenant->tenantApplications()->firstOrFail();
        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Aplikasi SIDBM berhasil ditambahkan ke BUMDesma Maju.');
        $this->assertSame($application->base_url, $tenantApplication->instance_url);
        $this->assertNull($tenantApplication->sub_tenant_code);
        $this->assertSame(40, strlen($tenantApplication->api_secret));
        $this->assertTrue($tenantApplication->is_active);
        $this->assertNotNull($tenantApplication->activated_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superadmin->id,
            'action' => 'quick_assign_app',
            'subject_type' => TenantApplication::class,
            'subject_id' => $tenantApplication->id,
        ]);
    }

    public function test_duplicate_active_assignment_is_rejected(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create();
        $existing = $tenant->tenantApplications()->create([
            'application_id' => $application->id,
            'label' => 'Existing',
            'instance_url' => $application->base_url,
            'api_secret' => 'existing-secret',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->from(route('dashboard'))
            ->post(route('admin.tenants.applications.quick-assign', $tenant), [
                'application_id' => $application->id,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas(
                'error',
                "Aplikasi {$application->name} dengan kode sub-tenant '' sudah terpasang di usaha ini."
            );

        $this->assertSame($existing->id, $tenant->tenantApplications()->firstOrFail()->id);
    }

    public function test_quick_assigns_a_sub_tenant_code(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create(['name' => 'SIDBM']);

        $this->actingAs($superadmin)
            ->from(route('dashboard'))
            ->post(route('admin.tenants.applications.quick-assign', $tenant), [
                'application_id' => $application->id,
                'sub_tenant_code' => ' sukamaju ',
            ]);

        $tenantApplication = $tenant->tenantApplications()->firstOrFail();
        $this->assertSame('sukamaju', $tenantApplication->sub_tenant_code);
    }

    public function test_allows_duplicate_application_with_a_different_sub_tenant_code(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create(['name' => 'SIDBM']);
        $existing = $tenant->tenantApplications()->create([
            'application_id' => $application->id,
            'label' => 'SIDBM',
            'instance_url' => $application->base_url,
            'sub_tenant_code' => 'mekarsari',
            'api_secret' => 'existing-secret',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->from(route('dashboard'))
            ->post(route('admin.tenants.applications.quick-assign', $tenant), [
                'application_id' => $application->id,
                'sub_tenant_code' => 'sukamaju',
            ])
            ->assertSessionHas('success', 'Aplikasi SIDBM berhasil ditambahkan ke '.$tenant->name.'.');

        $this->assertSame(2, $tenant->tenantApplications()->count());
        $this->assertSame('mekarsari', $existing->fresh()->sub_tenant_code);
        $this->assertSame('sukamaju', $tenant->tenantApplications()->latest('id')->first()->sub_tenant_code);
    }

    public function test_rejects_duplicate_application_with_the_same_sub_tenant_code(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create(['name' => 'SIDBM']);
        $existing = $tenant->tenantApplications()->create([
            'application_id' => $application->id,
            'label' => 'SIDBM',
            'instance_url' => $application->base_url,
            'sub_tenant_code' => 'sukamaju',
            'api_secret' => 'existing-secret',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->from(route('dashboard'))
            ->post(route('admin.tenants.applications.quick-assign', $tenant), [
                'application_id' => $application->id,
                'sub_tenant_code' => 'sukamaju',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas(
                'error',
                "Aplikasi SIDBM dengan kode sub-tenant 'sukamaju' sudah terpasang di usaha ini."
            );

        $this->assertSame(1, $tenant->tenantApplications()->count());
        $this->assertSame($existing->id, $tenant->tenantApplications()->firstOrFail()->id);
    }

    public function test_inactive_application_is_rejected(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->inactive()->create();

        $this->actingAs($superadmin)
            ->from(route('dashboard'))
            ->post(route('admin.tenants.applications.quick-assign', $tenant), [
                'application_id' => $application->id,
            ])
            ->assertSessionHasErrors('application_id');

        $this->assertDatabaseCount('tenant_applications', 0);
    }

    public function test_tenant_owner_cannot_quick_assign(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenant)->create();
        $application = Application::factory()->create();

        $this->actingAs($owner)
            ->post(route('admin.tenants.applications.quick-assign', $tenant), [
                'application_id' => $application->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('tenant_applications', 0);
    }

    public function test_dashboard_includes_active_master_applications_for_superadmin(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $application = Application::factory()->create(['name' => 'SIDBM']);
        Application::factory()->inactive()->create();

        $this->actingAs($superadmin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('availableApplications.0.id', $application->id)
                ->where('availableApplications.0.name', 'SIDBM'));
    }
}
