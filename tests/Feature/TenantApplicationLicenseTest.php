<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantApplicationLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_assign_update_show_and_revoke_tenant_application(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create();

        // Assign application
        $response = $this->actingAs($superadmin)->post(route('admin.tenants.applications.store', $tenant), [
            'application_id' => $application->id,
            'label' => 'Cabang Sentral',
            'instance_url' => 'https://sentral.tenant.test',
            'expired_at' => now()->addYear()->format('Y-m-d'),
            'notes' => 'Catatan lisensi pertama',
            'is_active' => true,
        ]);

        $tenantApp = TenantApplication::query()
            ->where('tenant_id', $tenant->id)
            ->where('application_id', $application->id)
            ->firstOrFail();

        $response->assertRedirect(route('admin.tenants.applications.show', [$tenant->id, $tenantApp->id]));
        $this->assertNotEmpty($tenantApp->api_secret);
        $this->assertSame(40, strlen($tenantApp->api_secret));
        $this->assertSame('Cabang Sentral', $tenantApp->label);

        // View index and show page
        $this->actingAs($superadmin)
            ->get(route('admin.tenants.applications.index', $tenant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/TenantApplications/Index'));

        $this->actingAs($superadmin)
            ->get(route('admin.tenants.applications.show', [$tenant, $tenantApp]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/TenantApplications/Show'));

        // Update application license
        $this->actingAs($superadmin)
            ->put(route('admin.tenants.applications.update', [$tenant, $tenantApp]), [
                'label' => 'Cabang Utama Baru',
                'instance_url' => 'https://utama.tenant.test',
                'expired_at' => now()->addMonths(6)->format('Y-m-d'),
                'notes' => 'Catatan diperbarui',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.tenants.applications.index', $tenant));

        $tenantApp->refresh();
        $this->assertSame('Cabang Utama Baru', $tenantApp->label);
        $this->assertFalse($tenantApp->is_active);

        // Revoke application license
        $this->actingAs($superadmin)
            ->delete(route('admin.tenants.applications.destroy', [$tenant, $tenantApp]))
            ->assertRedirect(route('admin.tenants.applications.index', $tenant));

        $this->assertModelMissing($tenantApp);
        $this->assertGreaterThanOrEqual(3, ActivityLog::query()->where('subject_type', TenantApplication::class)->count());
    }

    public function test_assigning_duplicate_application_to_same_tenant_is_rejected(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create();

        TenantApplication::factory()->create([
            'tenant_id' => $tenant->id,
            'application_id' => $application->id,
        ]);

        $this->actingAs($superadmin)->post(route('admin.tenants.applications.store', $tenant), [
            'application_id' => $application->id,
            'instance_url' => 'https://dup.tenant.test',
        ])->assertSessionHasErrors('application_id');
    }

    public function test_superadmin_can_regenerate_api_secret(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $tenantApp = TenantApplication::factory()->create(['tenant_id' => $tenant->id]);

        $oldSecret = $tenantApp->api_secret;

        $response = $this->actingAs($superadmin)
            ->post(route('admin.tenants.applications.regenerate-secret', [$tenant, $tenantApp]));

        $response->assertRedirect();
        $tenantApp->refresh();

        $this->assertNotSame($oldSecret, $tenantApp->api_secret);
        $this->assertSame(40, strlen($tenantApp->api_secret));
        $this->assertSame($tenantApp->api_secret, session('new_api_secret'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'regenerate_api_secret',
            'subject_type' => TenantApplication::class,
            'subject_id' => $tenantApp->id,
        ]);
    }

    public function test_non_superadmin_cannot_manage_tenant_applications(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenant)->create();
        $tenantApp = TenantApplication::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)
            ->get(route('admin.tenants.applications.index', $tenant))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('admin.tenants.applications.regenerate-secret', [$tenant, $tenantApp]))
            ->assertForbidden();
    }
}
