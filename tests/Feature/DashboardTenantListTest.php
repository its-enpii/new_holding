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

    public function test_superadmin_sees_all_tenants_with_application_and_license_counts(): void
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
                ->where('tenantList.0.applications.0.label', 'Instance Utama')
                ->where('tenantList.1.id', $inactiveTenant->id)
                ->where('tenantList.1.is_active', false)
                ->where('tenantList.1.applications_count', 1)
                ->where('tenantList.1.expiring_count', 0)
                ->where('tenantList.1.expired_count', 1));
    }
}
