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

final class AppAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_quick_access_active_app_and_gets_redirected_away(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantStaff()->for($tenant)->create();
        $application = Application::factory()->create(['name' => 'POS Desa']);

        $tenantApp = TenantApplication::factory()->create([
            'tenant_id' => $tenant->id,
            'application_id' => $application->id,
            'instance_url' => 'https://pos.desa.test/app',
            'is_active' => true,
            'expired_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->post(route('app.access', $tenantApp));

        $response->assertRedirect('https://pos.desa.test/app');

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'access_app',
            'subject_type' => TenantApplication::class,
            'subject_id' => $tenantApp->id,
        ]);
    }

    public function test_user_from_different_tenant_cannot_access_app(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->tenantStaff()->for($tenantA)->create();
        $tenantAppB = TenantApplication::factory()->create([
            'tenant_id' => $tenantB->id,
            'instance_url' => 'https://app-b.test',
        ]);

        $this->actingAs($userA)
            ->post(route('app.access', $tenantAppB))
            ->assertForbidden();

        $this->assertSame(0, ActivityLog::query()->where('action', 'access_app')->count());
    }

    public function test_accessing_inactive_or_expired_app_is_blocked(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();

        $inactiveApp = TenantApplication::factory()->inactive()->create([
            'tenant_id' => $tenant->id,
            'instance_url' => 'https://inactive.test',
        ]);

        $expiredApp = TenantApplication::factory()->expired()->create([
            'tenant_id' => $tenant->id,
            'instance_url' => 'https://expired.test',
        ]);

        $this->actingAs($user)
            ->post(route('app.access', $inactiveApp))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('app.access', $expiredApp))
            ->assertRedirect();
    }
}
