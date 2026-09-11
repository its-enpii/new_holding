<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTenantListTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_all_tenants_as_a_scalable_list(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create(['name' => 'BUMDesma Contoh']);
        $inactiveTenant = Tenant::factory()->inactive()->create();
        $application = Application::factory()->create(['name' => 'SIDBM']);

        TenantApplication::factory()->for($tenant)->create([
            'application_id' => Application::factory()->create(['name' => 'SIDBM Lama'])->id,
            'label' => 'Instance Utama',
            'instance_url' => 'https://sidbm.test',
            'is_active' => true,
            'expired_at' => now()->addDays(3),
        ]);
        TenantApplication::factory()->for($tenant)->for($application)->create([
            'label' => 'Instance Lama',
            'instance_url' => 'https://sidbm-lama.test',
            'is_active' => false,
            'expired_at' => now()->subDay(),
        ]);
        TenantApplication::factory()->for($inactiveTenant)->create([
            'instance_url' => 'https://lkm.test',
            'is_active' => true,
            'expired_at' => now()->subDay(),
        ]);

        $this->actingAs($superadmin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('tenantList.0.id', $tenant->id)
                ->where('tenantList.0.name', 'BUMDesma Contoh')
                ->where('tenantList.0.is_active', true)
                ->where('tenantList.0.applications_count', 2)
                ->where('tenantList.0.expiring_count', 1)
                ->where('tenantList.0.expired_count', 0)
                ->where('tenantList.1.id', $inactiveTenant->id)
                ->where('tenantList.1.is_active', false)
                ->where('tenantList.1.applications_count', 1)
                ->where('tenantList.1.expiring_count', 0)
                ->where('tenantList.1.expired_count', 1));
    }

    public function test_superadmin_sees_full_application_catalog_with_accurate_usage_counts(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $sidbm = Application::factory()->create(['name' => 'SIDBM']);
        $simak = Application::factory()->create(['name' => 'SIMAK']);
        $unused = Application::factory()->inactive()->create(['name' => 'Akubumdes']);
        $activeTenant = Tenant::factory()->create(['name' => 'BUMDesma Contoh']);
        $inactiveTenant = Tenant::factory()->inactive()->create(['name' => 'BUMDesma Tidak Aktif']);

        TenantApplication::factory()->for($activeTenant)->for($sidbm)->create([
            'is_active' => true,
            'connection_status' => 'connected',
        ]);
        TenantApplication::factory()->for($inactiveTenant)->for($sidbm)->create([
            'is_active' => true,
            'connection_status' => 'offline',
        ]);
        TenantApplication::factory()->for($activeTenant)->for($simak)->create([
            'is_active' => false,
            'connection_status' => 'connected',
        ]);

        $this->actingAs($superadmin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('applicationList.0.id', $unused->id)
                ->where('applicationList.0.name', 'Akubumdes')
                ->where('applicationList.0.tenants_using_count', 0)
                ->where('applicationList.0.active_licenses_count', 0)
                ->where('applicationList.1.id', $sidbm->id)
                ->where('applicationList.1.tenants_using_count', 2)
                ->where('applicationList.1.active_licenses_count', 2)
                ->where('applicationList.1.connected_count', 1)
                ->where('applicationList.1.connection_issues_count', 1)
                ->where('applicationList.2.id', $simak->id)
                ->where('applicationList.2.tenants_using_count', 1)
                ->where('applicationList.2.active_licenses_count', 0)
                ->where('applicationList.2.connected_count', 1)
                ->where('applicationList.2.connection_issues_count', 0)
                ->where('tenantList.0.id', $activeTenant->id)
                ->where('tenantList.1.id', $inactiveTenant->id));
    }
}
