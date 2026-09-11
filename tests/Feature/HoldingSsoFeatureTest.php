<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class HoldingSsoFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.holding_sso.secret' => null]);
        Cache::flush();
    }

    public function test_superadmin_exchanges_one_time_token_and_redirects_to_instance(): void
    {
        $superadmin = User::factory()->superadmin()->create([
            'name' => 'Superadmin Holding',
            'email' => 'superadmin@holding.test',
        ]);
        $tenant = Tenant::factory()->create(['name' => 'BUMDesma Contoh']);
        $tenantApplication = TenantApplication::factory()->for($tenant)->create([
            'label' => 'SIDBM Contoh',
            'instance_url' => 'https://sidbm.test',
            'is_active' => true,
            'expired_at' => now()->addMonth(),
        ]);
        $tenantApplication->application()->associate(Application::factory()->create(['name' => 'SIDBM']));
        $tenantApplication->save();

        $response = $this->actingAs($superadmin)
            ->get(route('admin.tenants.applications.sso', [$tenant, $tenantApplication]));

        $target = $response->headers->get('Location');
        $token = (string) parse_url($target, PHP_URL_QUERY);
        parse_str($token, $query);

        $response->assertRedirect();
        $this->assertSame('https://sidbm.test/auth/holding?token='.urlencode($query['token']), $target);
        $this->assertSame(64, strlen($query['token']));
        $this->assertSame(
            [
                'tenant_application_id' => $tenantApplication->id,
                'user_id' => $superadmin->id,
                'email' => $superadmin->email,
                'name' => $superadmin->name,
                'role' => $superadmin->role,
                'tenant_name' => $tenant->name,
                'exp' => now()->addMinute()->timestamp,
            ],
            Cache::get('sso:'.hash('sha256', $query['token']))
        );
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superadmin->id,
            'action' => 'sso_exchange',
            'subject_type' => TenantApplication::class,
            'subject_id' => $tenantApplication->id,
        ]);

        $secondResponse = $this->actingAs($superadmin)
            ->get(route('admin.tenants.applications.sso', [$tenant, $tenantApplication]));
        $secondTarget = $secondResponse->headers->get('Location');
        $secondToken = (string) parse_url($secondTarget, PHP_URL_QUERY);
        parse_str($secondToken, $secondQuery);

        $this->assertNotSame($query['token'], $secondQuery['token']);
        $this->assertNotNull(Cache::get('sso:'.hash('sha256', $secondQuery['token'])));
        $firstTokenKey = hash('sha256', $query['token']);
        Cache::forget("sso:{$firstTokenKey}");
        $this->assertNull(Cache::get("sso:{$firstTokenKey}"));
    }

    public function test_owner_exchanges_token_only_for_own_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenantA)->create();
        $tenantApplication = TenantApplication::factory()->for($tenantB)->create([
            'instance_url' => 'https://lkm.test',
            'is_active' => true,
            'expired_at' => now()->addMonth(),
        ]);

        $this->actingAs($owner)
            ->get(route('admin.tenants.applications.sso', [$tenantB, $tenantApplication]))
            ->assertForbidden();

        $this->assertSame(0, ActivityLog::query()->where('action', 'sso_exchange')->count());
    }

    public function test_tenant_staff_and_mismatched_tenant_are_blocked(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $tenantApplication = TenantApplication::factory()->for($tenant)->create([
            'instance_url' => 'https://akubumdes.test',
            'is_active' => true,
            'expired_at' => now()->addMonth(),
        ]);

        $this->actingAs(User::factory()->tenantStaff()->create())
            ->get(route('admin.tenants.applications.sso', [$tenant, $tenantApplication]))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->get(route('admin.tenants.applications.sso', [Tenant::factory()->create(), $tenantApplication]))
            ->assertNotFound();
    }

    public function test_inactive_and_expired_licenses_do_not_create_sso_tokens(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $inactiveApplication = TenantApplication::factory()->inactive()->for($tenant)->create([
            'instance_url' => 'https://inactive.test',
        ]);
        $expiredApplication = TenantApplication::factory()->expired()->for($tenant)->create([
            'instance_url' => 'https://expired.test',
        ]);

        $firstResponse = $this->actingAs($superadmin)
            ->get(route('admin.tenants.applications.sso', [$tenant, $inactiveApplication]));
        $secondResponse = $this->actingAs($superadmin)
            ->get(route('admin.tenants.applications.sso', [$tenant, $expiredApplication]));

        $this->assertTrue($firstResponse->isRedirection());
        $this->assertTrue($secondResponse->isRedirection());
        $this->assertFalse(Cache::has('anything'));
        $this->assertSame(0, ActivityLog::query()->where('action', 'sso_exchange')->count());
    }

    public function test_expired_sso_payload_is_invalid_for_subsidiary_exchange(): void
    {
        $token = 'expired-token';
        $cacheKey = 'sso:'.hash('sha256', $token);
        Cache::put(
            $cacheKey,
            ['exp' => now()->subMinute()->timestamp],
            now()->addMinute()
        );

        $payload = Cache::get($cacheKey);

        $this->assertLessThan(now()->timestamp, $payload['exp']);
        Cache::forget($cacheKey);
        $this->assertNull(Cache::get($cacheKey));
    }
}
